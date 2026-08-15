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
     * 端到端：装好字体后，中文（含粗体）必须真的渲染出来。
     *
     * 粗体单独探一次：只注册常规字面时，正文中文正常而标题/表头是问号 ——
     * 这种「一半对一半错」只探常规是发现不了的。
     */
    public function test_chinese_renders_once_the_font_is_installed(): void
    {
        if (!$this->cjkFontAvailable()) {
            $this->markTestSkipped('本机没有可用的中文 TrueType 字体，跳过端到端渲染');
        }

        // 字形数上万的中文字体，其 .ufm.json 度量缓存被整份 json_decode 成 PHP 数组，
        // 单次渲染峰值可达 120MB 上下（Arial Unicode 实测 119MB）。128M 的默认上限
        // 跑这条必炸，且炸在 phpunit 进程里会带走整个测试套件。
        if ($this->memoryLimitBytes() !== null && $this->memoryLimitBytes() < 384 * 1048576) {
            // 注意 `php artisan test` 另起子进程，不继承 -d，要直接调 phpunit
            $this->markTestSkipped(
                'memory_limit 低于 384M，嵌入中文字体的渲染会 OOM。手工验证：'
                . 'php -d memory_limit=1G vendor/bin/phpunit --filter=chinese_renders '
                . 'tests/Feature/PdfCjkFontTest.php'
            );
        }

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
