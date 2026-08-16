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
        // 「已注册」不等于「注册对了」。旧版本的本命令不做任何字形校验，装错字体
        // 也会写下这份映射；之后升级再跑本命令时直接在这里返回成功，新加的校验
        // 一次都碰不到 —— 目标机于是一直印着问号，而每次升级都报「已安装」。
        // 所以这条路径上也回读一次，过不了就当场重装。
        if ($this->isInstalled() && !$this->option('force')) {
            if ($this->verifyExistingInstall()) {
                return self::SUCCESS;
            }

            $this->warn('The registered CJK font failed verification - reinstalling.');
        }

        $source = $this->resolveSource();

        if ($source === null) {
            return self::FAILURE;
        }

        $this->line('Using font: ' . $source);

        if (!$this->assertChineseCoverage($source)) {
            return self::FAILURE;
        }

        $staged = $this->stageInsideChroot($source);
        if ($staged === null) {
            return self::FAILURE;
        }

        $this->purgeExistingRegistration();

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
     * 已注册的那份字体现在还好用吗。
     *
     * 两条都要过，缺一不可：
     *   1. 注册到 cjk 名下的那几个 TTF 真的有中文字形；
     *   2. 渲染出来的 PDF 确实嵌了它，而不是回退到自带字体。
     *
     * 只查第 2 条是不够的 —— 把一份纯拉丁字体登记成 cjk，渲染照样会嵌入
     * TrueType（/FontFile2 在、BaseFont 是子集名），回读全绿而中文仍是问号。
     * 旧版本的本命令不做任何字形校验，Win7 上留下的正是这种错误登记。
     *
     * 返回 false 时调用方会走完整的重装流程。
     */
    private function verifyExistingInstall(): bool
    {
        $registered = $this->registeredFontFiles();

        if ($registered === []) {
            $this->warn('The CJK registration points at font files that are missing; reinstalling.');

            return false;
        }

        foreach ($registered as $path) {
            $missing = $this->missingChineseGlyphs($path);

            if ($missing === null) {
                // cmap 读不出来，这一层给不出结论 —— 不当成通过，交给重装
                $this->warn('Could not read the character map of ' . basename($path) . '.');

                return false;
            }

            if ($missing !== []) {
                $this->warn(
                    'The font registered as "' . self::FAMILY . '" has no glyphs for: ' . implode(' ', $missing)
                );

                return false;
            }
        }

        $html = sprintf(
            '<html><body style="font-family: %s"><p>中文测试</p><p style="font-weight:bold">粗体中文</p></body></html>',
            self::FAMILY
        );

        try {
            $bytes = Pdf::loadHTML($html)->output();
        } catch (\Throwable $e) {
            $this->warn('The registered CJK font could not be rendered: ' . $e->getMessage());

            return false;
        }

        if (!$this->pdfEmbedsTheFont($bytes)) {
            return false;
        }

        $this->info('A CJK font is already registered and still renders Chinese. Use --force to reinstall.');

        return true;
    }

    /**
     * 注册到 cjk 名下的字体文件（每个字面一个）。
     *
     * installed-fonts.json 里存的是**不带扩展名**的路径，dompdf 用时再拼 .ttf；
     * 而且同一份清单里绝对路径和相对文件名会混着出现（相对的以 font_dir 为基准）。
     */
    private function registeredFontFiles(): array
    {
        $fontDir  = config('dompdf.options.font_dir');
        $manifest = $fontDir . '/installed-fonts.json';

        if (!is_file($manifest)) {
            return [];
        }

        $installed = json_decode((string) file_get_contents($manifest), true);
        $family = $installed[self::FAMILY] ?? null;

        if (!is_array($family)) {
            return [];
        }

        $files = [];

        foreach ($family as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $isAbsolute = str_starts_with($path, '/') || (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $path);
            $ttf = ($isAbsolute ? $path : $fontDir . '/' . $path) . '.ttf';

            if (is_file($ttf)) {
                $files[] = $ttf;
            }
        }

        return array_values(array_unique($files));
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
     * 这份 TTF 到底有没有中文字形。
     *
     * registerFont() 对字形一无所知：给它一份纯拉丁的 DejaVuSans 也照样返回 true，
     * 之后单据上的中文全是问号，而安装过程从头到尾显示成功。cmap 里查一次
     * 就能当场判定，比渲染完再猜便宜得多，所以放在注册之前。
     *
     * 用的是 dompdf 自带的 php-font-lib，getData() 只读 cmap 这一张表，
     * 不会像子集化那样把整份字体展开成 PHP 数组，内存开销可以忽略。
     */
    private function assertChineseCoverage(string $path): bool
    {
        $missing = $this->missingChineseGlyphs($path);

        if ($missing === null) {
            // 读不出 cmap 就当不通过。
            //
            // 原先这里放行，指望 verify() 的回读兜底 —— 但那一步只能证明「嵌进去的
            // 是这份字体」，证明不了这份字体有中文字形。两条都不成立时放行，
            // 等于又回到「命令成功、单据问号」的假成功，而这正是本命令要根治的。
            // 何况解析 cmap 用的就是 dompdf 嵌入字体时用的 php-font-lib：
            // 这里读不出来，嵌入本身也靠不住。
            $this->error('Could not read this font\'s character map, so its Chinese coverage cannot be verified.');
            $this->line('Refusing to install it - an unverified font is how printed Chinese became question marks.');
            $this->line('Pick a plain .ttf Chinese font, e.g. C:/Windows/Fonts/simhei.ttf');

            return false;
        }

        if ($missing !== []) {
            $this->error('This font has no glyphs for: ' . implode(' ', $missing));
            $this->line('Installing it would leave printed Chinese as question marks.');
            $this->line('Pick a Chinese font, e.g. C:/Windows/Fonts/simhei.ttf');

            return false;
        }

        return true;
    }

    /**
     * 这份 TTF 缺哪些中文字形。
     *
     * 返回空数组表示都有；返回 null 表示 cmap 读不出来，调用方自行决定怎么处理
     * （装新字体时放行交给回读兜底，校验旧登记时按不通过处理）。
     *
     * @return array<int, string>|null
     */
    private function missingChineseGlyphs(string $path): ?array
    {
        // 探针取自本命令的验证页与真实单据的高频字，覆盖常用汉字区（U+4E00–U+9FFF）
        $probes = ['中', '文', '测', '试', '粗', '体', '患', '者', '元'];

        try {
            $font = \FontLib\Font::load($path);
            $charMap = $font === null ? null : $font->getUnicodeCharMap();
        } catch (\Throwable $e) {
            $this->warn('Could not read the font ' . basename($path) . ': ' . $e->getMessage());

            return null;
        }

        if (!is_array($charMap) || $charMap === []) {
            return null;
        }

        $missing = [];

        foreach ($probes as $char) {
            $codepoint = mb_ord($char, 'UTF-8');

            if ($codepoint === false || !isset($charMap[$codepoint])) {
                $missing[] = $char;
            }
        }

        return $missing;
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
     * 注册前先把 cjk 名下的旧登记清干净。
     *
     * 不清的话「重装」是个静默空操作。dompdf 的 registerFont() 是这么定名的：
     *
     *     $remoteHash = md5($remoteFile);            // 源**路径**的 md5，不是内容
     *     $localFile  = "cjk_normal_" . $remoteHash;
     *     if (isset($entry[$style]) && $localFilePath == $entry[$style]) {
     *         return true;                           // 已登记同一路径 → 直接返回，不重读字体
     *     }
     *
     * 而本命令的暂存路径恒为 storage/app/fonts/cjk.ttf —— 换哪一份字体，算出来的
     * 目标名都一样。于是「检测到旧字体不对 → 重装」会返回 true 却什么都没换，
     * 旧字节原样留在 font_dir 里；--force 换字体同样换不掉。
     */
    private function purgeExistingRegistration(): void
    {
        $fontDir = config('dompdf.options.font_dir');

        foreach ((array) @glob($fontDir . '/' . self::FAMILY . '_*') as $stale) {
            @unlink($stale);
        }

        $manifest = $fontDir . '/installed-fonts.json';

        if (!is_file($manifest)) {
            return;
        }

        $installed = json_decode((string) file_get_contents($manifest), true);

        if (!is_array($installed) || !isset($installed[self::FAMILY])) {
            return;
        }

        unset($installed[self::FAMILY]);

        @file_put_contents($manifest, json_encode($installed, JSON_PRETTY_PRINT));
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
     * 真渲染一份带中文的 PDF，并回读产物确认用的是这份字体而不是回退字体。
     *
     * 此前这个方法的注释写着「回读确认中文不是问号」，实际只看了文件大小然后
     * 无条件 return true —— 装一份没有中文字形的 TTF 也会显示安装成功，等于
     * 把「没验证过的成功不算成功」这条教训自己违反了一遍。
     *
     * 现在按两条独立证据判定，都不依赖 pdftotext（目标机是 Win7，没有）：
     *
     * 1. **字体本身有没有中文字形** —— 由 assertChineseCoverage() 在注册前查
     *    cmap，直接排除「选错了一份纯拉丁 TTF」。
     * 2. **dompdf 有没有真用上它** —— 回读 PDF：嵌入 TrueType 时产物里必然有
     *    /FontFile2 与 `SUBxxx+字体名` 形式的 /BaseFont；一旦字族没解析上而
     *    回退到自带的 Helvetica，就只有 `/BaseFont /Helvetica`、没有
     *    /FontFile2 —— 那正是中文变问号的那种状态。实测两种情况的字节特征
     *    完全可分。
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

        if (!$this->pdfEmbedsTheFont($bytes, $fontPath)) {
            return false;
        }

        $this->reportMemoryHeadroom($fontPath);

        $this->info('Done. Chinese text will now render in printed PDFs.');
        $this->line('Registered as font-family: ' . self::FAMILY . ' (used by resources/views/printer_pdf/layout.blade.php)');

        return true;
    }

    /**
     * 回读产物：这页中文用的必须是嵌入的 TrueType，而不是 dompdf 自带的字体。
     *
     * 判据来自 PDF 结构，不需要解压页面内容流：
     *   嵌入 TrueType → FontDescriptor 里有 /FontFile2，/BaseFont 形如 /SUBAAB+SimHei
     *   回退 base-14  → 没有 /FontFile2，只有 /BaseFont /Helvetica
     * 这两个字典都是明文，不受内容流压缩影响。
     */
    private function pdfEmbedsTheFont(string $bytes, ?string $expectedFontPath = null): bool
    {
        if (strncmp($bytes, '%PDF', 4) !== 0) {
            $this->error('The verification render did not produce a PDF.');

            return false;
        }

        preg_match_all('#/BaseFont\s*/([A-Za-z0-9+,.\-]+)#', $bytes, $matches);
        $baseFonts = array_unique($matches[1] ?? []);

        if (strpos($bytes, '/FontFile2') === false) {
            $this->error('The verification PDF embeds no TrueType font program (/FontFile2 is missing).');
            $this->line('dompdf fell back to a built-in font, so Chinese would still print as question marks.');
            $this->line('Fonts used by that PDF: ' . ($baseFonts ? implode(', ', $baseFonts) : '(none found)'));
            $this->line('Check that ' . config('dompdf.options.font_dir') . '/installed-fonts.json lists "' . self::FAMILY . '".');

            return false;
        }

        // 页面上只有两段中文，任何一段落到 base-14 都说明字面没配齐
        // （典型是只注册了 normal，粗体那段回退成 Helvetica）。
        $fellBack = array_filter($baseFonts, static fn ($name) => strpos($name, '+') === false);

        if ($fellBack !== []) {
            $this->error('Part of the verification page fell back to a built-in font: ' . implode(', ', $fellBack));
            $this->line('That text would print as question marks. Re-run with --force.');

            return false;
        }

        // 嵌的必须是刚装的那一份。
        //
        // FontMetrics::getFont() 有一个进程级的 static $cache：本进程里只要渲染过
        // 一次 cjk，之后重新注册也换不动它。上面只查「嵌了某个 TrueType」的话，
        // 重装后回读到的可能仍是被替换掉的旧字体，而命令报成功 —— 这次修 Win7
        // 遗留错误登记时就实打实碰到了这个假绿。
        if ($expectedFontPath !== null) {
            $expected = $this->postscriptName($expectedFontPath);

            if ($expected !== null && !$this->baseFontsMatch($baseFonts, $expected)) {
                $this->error('The verification PDF embedded a different font than the one just installed.');
                $this->line('Expected: ' . $expected . '  Got: ' . implode(', ', $baseFonts));
                $this->line('Re-run the command in a fresh process: php artisan pdf:install-cjk-font --force');

                return false;
            }
        }

        $this->line('Readback OK - embedded font: ' . implode(', ', $baseFonts));

        return true;
    }

    /**
     * PDF 里的 /BaseFont 是 `SUBAAB+ArialUnicodeMS` 这种子集名，去掉前缀后
     * 与字体的 PostScript 名比对；两边都归一化掉连字符与空格。
     */
    private function baseFontsMatch(array $baseFonts, string $expected): bool
    {
        $normalize = static fn (string $name): string => strtolower(
            preg_replace('/[^A-Za-z0-9]/', '', preg_replace('/^[A-Z]{6}\+/', '', $name))
        );

        $want = $normalize($expected);

        if ($want === '') {
            return true;
        }

        foreach ($baseFonts as $name) {
            $got = $normalize($name);

            // 子集名可能只有前缀、后面是空的 —— Windows 自带的中文字体就是这样：
            // 2026-08-16 的实机日志里 simhei 装完是 `SUBAAB+`，加号后面什么都没有。
            // 这种情况下比不出名字，不能据此判「装了另一份字体」：那会在字体其实
            // 正常的机器上报一条吓人的错。取不到就跳过这一项，交给上面
            // /FontFile2 与「没有回退到 base-14」两条继续兜底。
            if ($got === '') {
                continue;
            }

            if ($got !== $want) {
                return false;
            }
        }

        return $baseFonts !== [];
    }

    private function postscriptName(string $path): ?string
    {
        try {
            $font = \FontLib\Font::load($path);
            $name = $font === null ? null : $font->getFontPostscriptName();
        } catch (\Throwable $e) {
            return null;
        }

        return is_string($name) && $name !== '' ? $name : null;
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
