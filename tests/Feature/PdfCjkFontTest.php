<?php

namespace Tests\Feature;

use App\Console\Commands\InstallCjkPdfFont;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * 打印单据里的中文曾经全是问号。
 *
 * dompdf 自带的 Helvetica/Times 只有拉丁字形，裸跑 Pdf::loadHTML("<p>中文</p>")
 * 就输出 ????。7 个打印页（处方、账单收据、报价单、化验单、病历、退费单）全中招 ——
 * 页面上看不出来，只有真去打印才发现单据没法用。
 *
 * 这组用例守三件事：字体族在共享布局里声明了、子集化开着、装好之后中文真能渲染。
 */
class PdfCjkFontTest extends TestCase
{
    /**
     * 子集化必须开着。
     *
     * 中文字体动辄 10-25MB，关着的时候 dompdf 会把整份字体嵌进 PDF，128M 的
     * memory_limit 当场爆 —— 而且报在 Cpdf.php 的 file_get_contents 上，
     * 看不出跟字体有关，很难查。
     */
    public function test_font_subsetting_is_enabled(): void
    {
        $this->assertTrue(
            config('dompdf.options.enable_font_subsetting'),
            'enable_font_subsetting 关掉后，嵌入中文字体会直接把内存打爆'
        );
    }

    /**
     * 共享打印布局必须声明中文字体族，否则装了也用不上。
     */
    public function test_shared_print_layout_declares_the_cjk_family(): void
    {
        $layout = File::get(resource_path('views/printer_pdf/layout.blade.php'));

        $this->assertStringContainsString(
            'font-family: ' . InstallCjkPdfFont::FAMILY,
            $layout,
            'printer_pdf.layout 没声明 cjk 字体族，7 个打印页的中文会退回问号'
        );

        // .header_text 曾经写死 sans-serif !important，会把页眉的诊所名压成问号
        $this->assertStringNotContainsString(
            'font-family: sans-serif !important',
            $layout,
            '有 !important 的 sans-serif 会盖掉 cjk，页眉中文又会变问号'
        );
    }

    /**
     * 页眉 logo 必须走文件系统路径。
     *
     * asset() 生成的是 http://host/images/logo.png，而 PDF 在命令行进程里渲染，
     * dompdf 取不到那个地址 —— 页眉会印一行 "Image not found or type unknown"，
     * 单据直接没法给患者。public_path() 在 dompdf 的 chroot 之内，能直接读。
     */
    public function test_print_layout_loads_the_logo_from_disk_not_a_url(): void
    {
        $layout = File::get(resource_path('views/printer_pdf/layout.blade.php'));

        $this->assertStringNotContainsString(
            "asset('images/logo.png')",
            $layout,
            'asset() 生成 HTTP 地址，dompdf 在 CLI 下取不到，页眉会印成 Image not found'
        );

        $this->assertStringContainsString(
            "public_path('images/logo.png')",
            $layout,
            '页眉 logo 应走 public_path()'
        );
    }

    /**
     * .ttc 要被明确拒绝并给出可操作的替代，而不是让 dompdf 抛一个没头没尾的 fatal。
     *
     * Windows 的宋体只有 simsun.ttc；php-font-lib 认得这个头，但返回的
     * TrueType\Collection 缺 dompdf 需要的三个方法。
     */
    public function test_ttc_font_is_rejected_with_an_actionable_message(): void
    {
        $fake = storage_path('app/fake-collection.ttc');
        File::ensureDirectoryExists(dirname($fake));
        File::put($fake, 'ttcf' . str_repeat("\0", 64));

        try {
            $this->artisan('pdf:install-cjk-font', ['path' => $fake, '--force' => true])
                ->expectsOutputToContain('TrueType Collection')
                ->expectsOutputToContain('simhei.ttf')
                ->assertExitCode(1);
        } finally {
            File::delete($fake);
        }
    }

    public function test_missing_font_file_is_reported(): void
    {
        $this->artisan('pdf:install-cjk-font', ['path' => '/no/such/font.ttf', '--force' => true])
            ->expectsOutputToContain('Font file not found')
            ->assertExitCode(1);
    }

    /**
     * 一份没有中文字形的 TTF 必须当场被拒，不能装完显示成功。
     *
     * registerFont() 对字形一无所知，给它纯拉丁字体照样返回 true；此前 verify()
     * 只看 PDF 大小然后无条件 return true，于是「安装成功」和「单据全是问号」
     * 可以同时成立。DejaVuSans 是 dompdf 自带的，正好是这种字体。
     */
    public function test_a_font_without_chinese_glyphs_is_rejected(): void
    {
        $latinOnly = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        if (!is_file($latinOnly)) {
            $this->markTestSkipped('dompdf 自带字体不在预期位置，跳过');
        }

        $this->artisan('pdf:install-cjk-font', ['path' => $latinOnly, '--force' => true])
            ->expectsOutputToContain('no glyphs for')
            ->assertExitCode(1);

        // 拒绝要发生在注册之前，不能把这份字体写进 installed-fonts.json
        $manifest = config('dompdf.options.font_dir') . '/installed-fonts.json';
        if (is_file($manifest)) {
            $installed = json_decode((string) File::get($manifest), true);
            $family = $installed[InstallCjkPdfFont::FAMILY] ?? null;

            if (is_array($family)) {
                foreach ($family as $path) {
                    $this->assertStringNotContainsString(
                        'DejaVuSans',
                        (string) $path,
                        '拉丁字体被注册成了中文字体族'
                    );
                }
            }
        }
    }

    /**
     * 旧版本留下的错误登记不能被「已安装」短路掉。
     *
     * 早先的本命令不做任何字形校验，装错字体也会写下这份映射。之后升级再跑本命令
     * 时若直接在 isInstalled() 返回成功，新加的校验一次都碰不到 —— 目标机一直印着
     * 问号，而每次升级都报「已安装」。
     *
     * 这条不需要本机有中文字体：把纯拉丁的 DejaVuSans 登记成 cjk，再不带 --force
     * 跑一次，命令必须发现登记有问题并走重装流程（本例里重装源同样是那份纯拉丁
     * 字体，所以最终以「没有中文字形」失败）。旧实现会直接退 0。
     */
    public function test_a_bad_legacy_registration_is_not_reported_as_installed(): void
    {
        $latinOnly = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        if (!is_file($latinOnly)) {
            $this->markTestSkipped('dompdf 自带字体不在预期位置，跳过');
        }

        $this->restoreFontDirAfterwards();
        $this->registerAsCjk($latinOnly);

        $this->artisan('pdf:install-cjk-font', ['path' => $latinOnly])
            ->expectsOutputToContain('no glyphs for')
            ->assertExitCode(1);
    }

    /**
     * 发现旧登记不对之后，必须真的换掉那份字体。
     *
     * dompdf 的 registerFont() 按**源路径**的 md5 命名缓存文件，而本命令的暂存路径
     * 恒为 storage/app/fonts/cjk.ttf —— 换哪份字体算出来的目标名都一样，于是
     * 「已登记同一路径」这条短路会让重装变成静默空操作，旧字节原样留着。
     * 实测过：不清理时重装完回读到的仍是被替换掉的 DejaVuSans。
     */
    /**
     * 独立进程跑。
     *
     * dompdf 的 FontMetrics::getFont() 里是一个**函数级** static $cache：一旦本用例
     * 渲染过 CJK 字体，「家族 → cjk_bold_<hash> 文件」这条映射就在当前 PHP 进程里
     * 定死了，外部没有任何接口能清掉它。用例结束后按字节还原字体目录（文件被删或
     * 改回原样），可那份缓存还指着已经不存在的路径 —— 同一进程里后面任何一个渲染
     * PDF 的测试都会挂在 Text.php 的「Undefined array key .../cjk_bold_...」上。
     *
     * 这个用例此前一直被 memory_limit 门跳过，所以从没暴露过；把测试内存上限
     * 显式设成 512M 之后才真的跑起来，RegressionTest 的发票 PDF 当场 500。
     * 隔离进程是唯一能连静态缓存一起丢掉的办法。
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_a_bad_legacy_registration_is_actually_replaced(): void
    {
        if (!$this->cjkFontAvailable()) {
            $this->markTestSkipped('本机没有可用的中文 TrueType 字体，跳过');
        }

        if ($this->memoryLimitBytes() !== null && $this->memoryLimitBytes() < 384 * 1048576) {
            $this->markTestSkipped('memory_limit 低于 384M，嵌入中文字体的渲染会 OOM');
        }

        $latinOnly = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $this->restoreFontDirAfterwards();
        $this->registerAsCjk($latinOnly);

        $this->artisan('pdf:install-cjk-font')
            ->expectsOutputToContain('no glyphs for')
            ->expectsOutputToContain('Readback OK')
            ->assertExitCode(0);

        $bytes = Pdf::loadHTML(sprintf(
            '<html><body style="font-family: %s"><p>中文测试</p></body></html>',
            InstallCjkPdfFont::FAMILY
        ))->output();

        $this->assertStringNotContainsString(
            'DejaVuSans',
            $bytes,
            '重装是空操作：被替换掉的旧字体仍然嵌在产物里'
        );
    }

    /**
     * 把指定字体登记到 cjk 名下，用来伪造「旧版本留下的错误登记」。
     *
     * 必须先把已有的 cjk 登记清干净，否则这个辅助方法自己就会踩上它要复现的那个坑：
     * registerFont() 见到 cjk/normal 已经指向同一个目标路径（目标名由**源路径**的
     * md5 算出，而暂存路径恒为 storage/app/fonts/cjk.ttf）就直接 return true，
     * 伪造的坏登记根本没写进去 —— 本机原本装了中文字体时，这两条用例会假过。
     */
    private function registerAsCjk(string $source): void
    {
        $fontDir = config('dompdf.options.font_dir');

        foreach ((array) @glob($fontDir . '/' . InstallCjkPdfFont::FAMILY . '_*') as $stale) {
            File::delete($stale);
        }

        $manifest = $fontDir . '/installed-fonts.json';
        if (is_file($manifest)) {
            $installed = json_decode(File::get($manifest), true);
            unset($installed[InstallCjkPdfFont::FAMILY]);
            File::put($manifest, json_encode($installed, JSON_PRETTY_PRINT));
        }

        $staged = storage_path('app/fonts/' . InstallCjkPdfFont::FAMILY . '.ttf');
        File::ensureDirectoryExists(dirname($staged));
        File::copy($source, $staged);

        $metrics = Pdf::loadHTML('<p>x</p>')->getDomPDF()->getFontMetrics();

        foreach ([['weight' => 'normal', 'style' => 'normal'], ['weight' => 'bold', 'style' => 'normal']] as $style) {
            $metrics->registerFont(['family' => InstallCjkPdfFont::FAMILY] + $style, $staged);
        }
    }

    /**
     * 子集名为空时不能判成「装了另一份字体」。
     *
     * Windows 自带的中文字体就是这样：2026-08-16 的 Win7 实机日志里，simhei 装完
     * 回读到的是 `SUBAAB+, SUBAAC+` —— 加号后面什么都没有。名字比不出来时应当跳过
     * 这一项（交给 /FontFile2 与「没回退到 base-14」兜底），而不是报错说装错了字体：
     * 那会在字体其实完全正常的机器上吓人一跳。名字确实对不上时仍然要判失败。
     */
    public function test_an_empty_subset_name_is_not_treated_as_a_different_font(): void
    {
        $command = (new \ReflectionClass(InstallCjkPdfFont::class))->newInstanceWithoutConstructor();
        $match = (new \ReflectionClass(InstallCjkPdfFont::class))->getMethod('baseFontsMatch');
        $match->setAccessible(true);

        // 真机上的形态：只有子集前缀，没有字体名
        $this->assertTrue(
            $match->invoke($command, ['SUBAAB+', 'SUBAAC+'], 'SimHei'),
            '空的子集名比不出名字，不该判成装错了字体'
        );

        $this->assertTrue($match->invoke($command, ['SUBAAB+ArialUnicodeMS'], 'ArialUnicodeMS'));

        // 但名字确实对不上时，这道校验仍要拦住
        $this->assertFalse(
            $match->invoke($command, ['SUBAAB+DejaVuSans'], 'SimHei'),
            '名字对不上仍须判失败，否则这道校验就白加了'
        );
        $this->assertFalse($match->invoke($command, ['SUBAAB+', 'SUBAAC+DejaVuSans'], 'SimHei'));
    }

    /**
     * 命令的自检必须真的看产物，而不是「渲染没抛异常就算过」。
     *
     * 回退到 dompdf 自带的 base-14 字体时（中文变问号的那种状态），PDF 里
     * 只有 /BaseFont /Helvetica、没有 /FontFile2；嵌入 TrueType 时两者都在，
     * 且 BaseFont 形如 SUBAAB+字体名。这条钉的就是这个可分性 —— 它是
     * InstallCjkPdfFont::pdfEmbedsTheFont() 的判据来源。
     */
    public function test_a_fallback_render_is_distinguishable_from_an_embedded_one(): void
    {
        $fallback = Pdf::loadHTML('<p style="font-family: helvetica">Hello</p>')->output();
        $embedded = Pdf::loadHTML('<p style="font-family: DejaVu Sans">Hello</p>')->output();

        $this->assertStringNotContainsString('/FontFile2', $fallback, '回退渲染不该嵌入字体程序');
        $this->assertStringContainsString('/BaseFont /Helvetica', $fallback);

        $this->assertStringContainsString('/FontFile2', $embedded, '嵌入渲染必须带字体程序');
        $this->assertMatchesRegularExpression('#/BaseFont\s*/[A-Z]{6}\+#', $embedded);
    }

    /**
     * 端到端：装好字体后，中文（含粗体）必须真的渲染出来。
     *
     * 粗体单独探一次：只注册常规字面时，正文中文正常而标题/表头是问号 ——
     * 这种「一半对一半错」只探常规是发现不了的。
     */
    /**
     * 独立进程跑。
     *
     * dompdf 的 FontMetrics::getFont() 里是一个**函数级** static $cache：一旦本用例
     * 渲染过 CJK 字体，「家族 → cjk_bold_<hash> 文件」这条映射就在当前 PHP 进程里
     * 定死了，外部没有任何接口能清掉它。用例结束后按字节还原字体目录（文件被删或
     * 改回原样），可那份缓存还指着已经不存在的路径 —— 同一进程里后面任何一个渲染
     * PDF 的测试都会挂在 Text.php 的「Undefined array key .../cjk_bold_...」上。
     *
     * 这个用例此前一直被 memory_limit 门跳过，所以从没暴露过；把测试内存上限
     * 显式设成 512M 之后才真的跑起来，RegressionTest 的发票 PDF 当场 500。
     * 隔离进程是唯一能连静态缓存一起丢掉的办法。
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_chinese_renders_once_the_font_is_installed(): void
    {
        if (!$this->cjkFontAvailable()) {
            $this->markTestSkipped('本机没有可用的中文 TrueType 字体，跳过端到端渲染');
        }

        // 渲染峰值约为字体文件的 3.5 倍（dompdf 做子集时把整份字体读进来两遍）。
        // 开发机上唯一可用的是 22MB 的 Arial Unicode，峰值 121MB，加上 phpunit
        // 自身的开销，128M 必炸且会带走整个套件。生产用的黑体只有 9.7MB，
        // 峰值约 78MB，默认上限完全够 —— 这条跳过反映的是开发机字体偏大，
        // 不是生产环境的约束。
        if ($this->memoryLimitBytes() !== null && $this->memoryLimitBytes() < 384 * 1048576) {
            // 注意 `php artisan test` 另起子进程，不继承 -d，要直接调 phpunit
            $this->markTestSkipped(
                'memory_limit 低于 384M，嵌入中文字体的渲染会 OOM。手工验证：'
                . 'php -d memory_limit=1G vendor/bin/phpunit --filter=chinese_renders '
                . 'tests/Feature/PdfCjkFontTest.php'
            );
        }

        // 装完必须还原。dompdf 的注册是写进 storage/fonts 的全局状态，留在那儿会
        // 让**之后每一次**跑套件的 PDF 渲染都嵌入这份 20MB+ 的字体 —— 在
        // memory_limit 128M 的机器上（本条用例正是因此被跳过的那种机器）整片 OOM，
        // 而且报在 Cpdf.php 上，看不出跟这条用例有关。
        $this->restoreFontDirAfterwards();

        $this->artisan('pdf:install-cjk-font', ['--force' => true])->assertExitCode(0);

        $html = sprintf(
            '<html><body style="font-family: %s"><p>退费单据</p><p style="font-weight:bold">粗体标题</p></body></html>',
            InstallCjkPdfFont::FAMILY
        );

        $bytes = Pdf::loadHTML($html)->output();

        $this->assertStringStartsWith('%PDF', $bytes);

        // 子集化生效时一页中文在几十 KB；上兆说明整份字体被嵌进去了
        $this->assertLessThan(
            2 * 1024 * 1024,
            strlen($bytes),
            'PDF 异常大，子集化多半没生效'
        );

        $text = $this->extractText($bytes);

        if ($text === null) {
            $this->markTestSkipped('本机没有 pdftotext，跳过文本回读');
        }

        $this->assertStringContainsString('退费单据', $text);
        $this->assertStringContainsString('粗体标题', $text, '粗体中文变问号：只注册了常规字面');
        $this->assertStringNotContainsString('??', $text);
    }

    /**
     * 真渲染一份走共享布局的单据，确认 logo 读得到。
     *
     * 上一条只查 blade 源码里的写法，这条查实际产物 —— dompdf 取不到图时会把
     * "Image not found or type unknown" 印进 PDF，回读文本就能抓到。
     */
    public function test_rendered_receipt_has_no_missing_image_placeholder(): void
    {
        if (!is_file(public_path('images/logo.png'))) {
            $this->markTestSkipped('public/images/logo.png 不存在，跳过');
        }

        $html = '<html><body><img src="' . public_path('images/logo.png') . '" style="width:140px"></body></html>';

        $text = $this->extractText(Pdf::loadHTML($html)->output());

        if ($text === null) {
            $this->markTestSkipped('本机没有 pdftotext，跳过文本回读');
        }

        $this->assertStringNotContainsString('Image not found', $text);
    }

    /**
     * 记下 dompdf 字体目录的当前状态，用例结束后还原。
     *
     * 装字体改的是 storage/fonts 里的全局注册（installed-fonts.json + 每个字面
     * 一份 20MB+ 的副本），不还原就会漏给之后所有跑套件的人。
     *
     * 只记文件名是不够的：安装命令的 purgeExistingRegistration() 会**删掉** cjk_*
     * 再重建，所以本机原本就装了中文字体时，那几个文件的内容会被换掉、或换成
     * 另一个哈希名。清单恢复了、文件却对不上，等于把开发机的字体注册搞坏。
     * 因此连字节一起备份 —— 只备份 cjk_* 与暂存文件，其余字面（Helvetica.afm.json
     * 之类）本命令不碰。
     */
    private function restoreFontDirAfterwards(): void
    {
        $fontDir  = config('dompdf.options.font_dir');
        $manifest = $fontDir . '/installed-fonts.json';
        $staged   = storage_path('app/fonts/' . InstallCjkPdfFont::FAMILY . '.ttf');

        $before         = is_dir($fontDir) ? array_flip((array) scandir($fontDir)) : [];
        $manifestBefore = is_file($manifest) ? File::get($manifest) : null;
        $stagedBefore   = is_file($staged) ? File::get($staged) : null;

        // 命令会动的那批文件，连内容一起留底
        $bytesBefore = [];
        foreach ((array) @glob($fontDir . '/' . InstallCjkPdfFont::FAMILY . '_*') as $owned) {
            $bytesBefore[basename($owned)] = File::get($owned);
        }

        $this->beforeApplicationDestroyed(function () use ($fontDir, $manifest, $staged, $before, $manifestBefore, $stagedBefore, $bytesBefore) {
            foreach ((array) (is_dir($fontDir) ? scandir($fontDir) : []) as $entry) {
                if ($entry !== '.' && $entry !== '..' && !isset($before[$entry])) {
                    @unlink($fontDir . '/' . $entry);
                }
            }

            // 被 purge 删掉或被覆盖的，按原字节写回去
            foreach ($bytesBefore as $name => $bytes) {
                File::put($fontDir . '/' . $name, $bytes);
            }

            if ($manifestBefore === null) {
                @unlink($manifest);
            } else {
                File::put($manifest, $manifestBefore);
            }

            if ($stagedBefore === null) {
                @unlink($staged);
            } else {
                File::ensureDirectoryExists(dirname($staged));
                File::put($staged, $stagedBefore);
            }
        });
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

    private function cjkFontAvailable(): bool
    {
        foreach ((new \ReflectionClass(InstallCjkPdfFont::class))->getConstants()['CANDIDATES'] ?? [] as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 回读 PDF 文本。没有 pdftotext 的机器上返回 null，由调用方跳过。
     */
    private function extractText(string $bytes): ?string
    {
        exec('command -v pdftotext', $out, $code);

        if ($code !== 0) {
            return null;
        }

        $pdf = tempnam(sys_get_temp_dir(), 'cjk-') . '.pdf';
        file_put_contents($pdf, $bytes);

        $text = shell_exec('pdftotext ' . escapeshellarg($pdf) . ' - 2>/dev/null');

        @unlink($pdf);

        return $text === null ? '' : $text;
    }
}
