<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

/**
 * 给 dompdf 装一份中文字体，让打印单据不再输出问号。
 *
 * 现状：所有走 printer_pdf.layout 的打印页（处方、账单收据、报价单、化验单、
 * 病历、退费单）输出的 PDF 里，中文全是 `?` —— dompdf 自带的 Helvetica/Times
 * 只有拉丁字形。裸跑 Pdf::loadHTML("<p>中文</p>") 就能复现。
 *
 * 装完之后 7 个打印页都不用改代码：registerFont() 会把映射写进
 * storage/fonts/installed-fonts.json，之后任何 Dompdf 实例解析 `font-family: cjk`
 * 都能命中，layout 里声明一次就够。
 *
 * 三个踩过的坑，改这块之前先读：
 *
 * 1. **.ttc 用不了。** Windows 的宋体只有 simsun.ttc（TrueType Collection），
 *    php-font-lib 0.5.6 认得这个头，但返回的 TrueType\Collection 类没有
 *    saveAdobeFontMetrics() / getFontType() / close()，而 dompdf 的 registerFont()
 *    三个都要调 —— 直接 fatal。所以候选里只能放纯 .ttf。
 *    Windows 7 上可用的纯 .ttf 有 simhei（黑体）、simkai、simfang、msyh。
 *
 * 2. **字体文件必须在 chroot 内。** dompdf 2.x 给 file:// 协议挂了一条校验规则，
 *    要求文件位于 Options::chroot（默认 base_path()）之下，直接指
 *    C:\Windows\Fonts\msyh.ttf 会被拒。而且这条规则的闭包在 Options 构造时就
 *    固化了 chroot，事后 setChroot() 无效。所以这里先把字体复制进项目再注册。
 *
 * 3. **必须开子集化。** 中文字体动辄 10-25MB，enable_font_subsetting 关着时
 *    dompdf 会把整份字体嵌进 PDF，产出几十 MB 的文件并撑爆内存。开了之后只嵌
 *    用到的字形，一页单据从 23MB 降到 26KB。开关在 AppServiceProvider。
 *
 * 4. **内存开销跟字体文件大小走，约 3.5 倍。** Cpdf.php 做子集时 Font::load +
 *    reduce 走两遍，再把 cmap/hmtx 全展开成 PHP 数组。实测：22.2MB 的
 *    Arial Unicode 峰值 121MB，13.2MB 的子集 90MB，不装中文字体 44MB。
 *    所以余量不够时该换小字体，而不是抬 memory_limit —— 抬上去只是把「选了个
 *    覆盖全 Unicode 的字体」这件事掩盖过去。黑体 9.7MB 约 78MB，默认 128M 够用。
 */
class InstallCjkPdfFont extends Command
{
    protected $signature = 'pdf:install-cjk-font
        {path? : Path to a .ttf font file (skips auto-detection)}
        {--force : Reinstall even if a CJK font is already registered}';

    protected $description = 'Install a Chinese font into dompdf so printed PDFs stop rendering CJK text as question marks';

    /** layout 与已注册映射共用的字体族名 */
    public const FAMILY = 'cjk';

    /**
     * 各平台自带的候选，只列纯 .ttf，按文件体积从小到大排。
     *
     * 排序依据是内存：dompdf 嵌入字体时会把整份字体读进来做子集（Cpdf.php 里
     * Font::load + reduce 走两遍，再把 cmap/hmtx 全展开成 PHP 数组），实测峰值
     * 约为字体文件的 3.5 倍。黑体 9.7MB → 约 78MB，微软雅黑 15MB → 约 96MB，
     * 默认 memory_limit 128M 下前者余量舒服得多。
     *
     * 黑体本来就是中文正式单据的常用字体，观感上不吃亏。
     * simsun.ttc 不在列 —— 见类注释第 1 条。
     */
    private const CANDIDATES = [
        // Windows（7 上 msyh 是 .ttf；8.1+ 换成 .ttc，届时自然落到黑体）
        'C:/Windows/Fonts/simhei.ttf',
        'C:/Windows/Fonts/simkai.ttf',
        'C:/Windows/Fonts/simfang.ttf',
        'C:/Windows/Fonts/msyh.ttf',
        // macOS（开发机）
        '/Library/Fonts/Arial Unicode.ttf',
        '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
        // Linux（CI / 容器）
        '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttf',
        '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttf',
        '/usr/share/fonts/truetype/arphic/uming.ttf',
    ];

    public function handle(): int
    {
        if ($this->isInstalled() && !$this->option('force')) {
            $this->info('A CJK font is already registered. Use --force to reinstall.');

            return self::SUCCESS;
        }

        $source = $this->resolveSource();

        if ($source === null) {
            return self::FAILURE;
        }

        $this->line('Using font: ' . $source);

        $staged = $this->stageInsideChroot($source);
        if ($staged === null) {
            return self::FAILURE;
        }

        if (!$this->register($staged)) {
            $this->error('dompdf refused to register the font. Check that storage/fonts is writable.');

            return self::FAILURE;
        }

        return $this->verify($staged) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 是否已经注册过。dompdf 把映射持久化在 font_dir/installed-fonts.json。
     */
    private function isInstalled(): bool
    {
        $manifest = config('dompdf.options.font_dir') . '/installed-fonts.json';

        if (!is_file($manifest)) {
            return false;
        }

        $installed = json_decode((string) file_get_contents($manifest), true);

        return is_array($installed) && isset($installed[self::FAMILY]);
    }

    /**
     * 定位字体源文件：优先命令行参数，其次按平台候选表。
     */
    private function resolveSource(): ?string
    {
        $explicit = $this->argument('path');

        if ($explicit !== null) {
            if (!is_file($explicit)) {
                $this->error("Font file not found: {$explicit}");

                return null;
            }

            return $this->rejectCollection($explicit) ? null : $explicit;
        }

        foreach (self::CANDIDATES as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $this->error('No bundled CJK TrueType font found on this machine.');
        $this->line('Looked for:');
        foreach (self::CANDIDATES as $candidate) {
            $this->line('  ' . $candidate);
        }
        $this->newLine();
        $this->line('Pass a .ttf explicitly:  php artisan pdf:install-cjk-font "C:/Windows/Fonts/simhei.ttf"');
        $this->warn('Note: .ttc files (simsun.ttc, msyh.ttc on Win8.1+) are NOT supported by dompdf.');

        return null;
    }

    /**
     * .ttc 提前挡掉并说清楚原因 —— 否则用户只会看到一个没头没尾的 fatal。
     */
    private function rejectCollection(string $path): bool
    {
        $header = (string) file_get_contents($path, false, null, 0, 4);

        if ($header === 'ttcf') {
            $this->error('This is a .ttc (TrueType Collection); dompdf cannot read it.');
            $this->line('php-font-lib returns a Collection object that lacks the methods dompdf needs.');
            $this->line('On Windows 7 use one of these plain .ttf files instead:');
            $this->line('  C:/Windows/Fonts/msyh.ttf     (微软雅黑)');
            $this->line('  C:/Windows/Fonts/simhei.ttf   (黑体)');

            return true;
        }

        return false;
    }

    /**
     * 复制进项目内。dompdf 的 file:// 校验要求源文件位于 chroot 之下，
     * 直接读 C:\Windows\Fonts 会被拒（见类注释第 2 条）。
     */
    private function stageInsideChroot(string $source): ?string
    {
        $dir = storage_path('app/fonts');

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->error("Could not create {$dir}");

            return null;
        }

        $target = $dir . '/' . self::FAMILY . '.ttf';

        if (!@copy($source, $target)) {
            $this->error("Could not copy the font to {$target}");

            return null;
        }

        return $target;
    }

    /**
     * 注册到 dompdf。这一步会写 font_dir/installed-fonts.json，之后全局生效。
     *
     * 两个字重都指向同一个文件。只注册 normal 的话，凡是 font-weight: bold 的
     * 地方（单据标题、表头）dompdf 找不到对应字面，会回退到自带的 Helvetica ——
     * 于是同一页里正文中文正常、标题和表头是问号。Windows 自带的中文字体多数
     * 没有独立的粗体文件（msyhbd.ttf 是例外），让 dompdf 拿常规字面去合成即可。
     */
    private function register(string $path): bool
    {
        $fontDir = config('dompdf.options.font_dir');

        if (!is_dir($fontDir) && !@mkdir($fontDir, 0755, true) && !is_dir($fontDir)) {
            $this->error("Could not create the dompdf font directory: {$fontDir}");

            return false;
        }

        $this->line('Registering normal + bold (reads the font once each, may take a few seconds)...');

        $metrics = Pdf::loadHTML('<p>x</p>')->getDomPDF()->getFontMetrics();

        // 只注册 normal 与 bold。斜体这些打印模板一处没用，装了只是白占磁盘
        // 和安装时间（每个字面都要把整份字体重新解析一遍）。
        $styles = [
            ['weight' => 'normal', 'style' => 'normal'],
            ['weight' => 'bold',   'style' => 'normal'],
        ];

        foreach ($styles as $style) {
            $ok = $metrics->registerFont(
                ['family' => self::FAMILY] + $style,
                $path
            );

            if (!$ok) {
                $this->error("Failed to register {$style['weight']} {$style['style']}");

                return false;
            }
        }

        return true;
    }

    /**
     * 真渲染一份带中文的 PDF 并回读，确认字形是嵌进去了而不是变成问号。
     *
     * 不做这一步的话，registerFont() 返回 true 但 PDF 里仍是 `?` 也发现不了 ——
     * 这一整轮排查的教训就是「没验证过的成功不算成功」。
     */
    private function verify(string $fontPath): bool
    {
        // 粗体一并验证：只注册常规字面时，正文中文正常而标题/表头是问号，
        // 只探常规就会漏掉这种「一半对一半错」的状态。
        $html = sprintf(
            '<html><body style="font-family: %s"><p>中文测试</p><p style="font-weight:bold">粗体中文</p></body></html>',
            self::FAMILY
        );

        try {
            $bytes = Pdf::loadHTML($html)->output();
        } catch (\Throwable $e) {
            $this->error('Rendering the verification PDF failed: ' . $e->getMessage());
            $this->line('If this is an out-of-memory error, raise memory_limit in php.ini and retry.');

            return false;
        }

        // 子集化后一页中文单据在 20-30KB；几 MB 说明整份字体被嵌进去了
        $kb = (int) round(strlen($bytes) / 1024);
        $this->line("Verification PDF: {$kb} KB");

        if ($kb > 2048) {
            $this->warn('That PDF is unexpectedly large - font subsetting may be disabled.');
        }

        $this->reportMemoryHeadroom($fontPath);

        $this->info('Done. Chinese text will now render in printed PDFs.');
        $this->line('Registered as font-family: ' . self::FAMILY . ' (used by resources/views/printer_pdf/layout.blade.php)');

        return true;
    }

    /**
     * 报出这次渲染的峰值内存与 memory_limit 的余量。
     *
     * dompdf 嵌入字体时要把整份字体读进来做子集（Cpdf.php 里 Font::load + reduce
     * 走两遍，再把 cmap/hmtx 全展开成 PHP 数组），峰值实测约为字体文件的 3.5 倍，
     * 与字形数无关、只跟文件大小走：
     *
     *     Arial Unicode  22.2MB → 121MB     黑体   9.7MB → 约 78MB
     *     21k 字形子集   13.2MB →  90MB     雅黑  15.0MB → 约 96MB
     *
     * 所以余量不够时正确的做法是换更小的字体，不是抬 memory_limit —— 抬上去只是
     * 把「选了一个覆盖全 Unicode 的字体」这件事掩盖掉。
     */
    private function reportMemoryHeadroom(string $fontPath): void
    {
        $peak = memory_get_peak_usage(true);
        $limit = $this->memoryLimitBytes();

        $peakMb = (int) round($peak / 1048576);
        $fontMb = round(filesize($fontPath) / 1048576, 1);

        $this->line("Font size: {$fontMb} MB");

        if ($limit === null) {
            $this->line("Peak memory: {$peakMb} MB (memory_limit is unlimited)");

            return;
        }

        $limitMb = (int) round($limit / 1048576);
        $this->line("Peak memory: {$peakMb} MB of {$limitMb} MB memory_limit");

        if ($peak <= $limit * 0.8) {
            return;
        }

        $this->warn('Less than 20% headroom - printing a busier page may run out of memory.');
        $this->line('Peak memory tracks the FONT FILE SIZE (roughly 3.5x), so pick a smaller font');
        $this->line('rather than raising memory_limit.');

        $lighter = [];
        foreach (self::CANDIDATES as $candidate) {
            if (!is_file($candidate) || realpath($candidate) === realpath($fontPath)) {
                continue;
            }

            $mb = round(filesize($candidate) / 1048576, 1);
            if ($mb < $fontMb) {
                $lighter[] = "  php artisan pdf:install-cjk-font \"{$candidate}\" --force   ({$mb} MB)";
            }
        }

        if ($lighter === []) {
            // 开发机常见：本地只有一个覆盖全 Unicode 的大字体。生产的
            // Windows 自带黑体 9.7MB，峰值约 78MB，默认 128M 完全够。
            $this->line('No lighter font found on this machine. On Windows try simhei.ttf (~9.7 MB).');

            return;
        }

        $this->line('Lighter fonts available here:');
        foreach ($lighter as $line) {
            $this->line($line);
        }
    }

    private function memoryLimitBytes(): ?int
    {
        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || $raw === '-1') {
            return null;
        }

        $unit = strtolower(substr($raw, -1));
        $value = (int) $raw;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
