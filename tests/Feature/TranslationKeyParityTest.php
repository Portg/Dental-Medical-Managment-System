<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * zh-CN 有、en 没有的翻译键，只要代码里真的引用了，切到英文就直出 key 名本身——
 * 页面上蹦出 `invoices.discount_not_pending` 这种东西。
 *
 * fallback_locale 是 en，所以反方向（en 有 zh-CN 没有）会回落成英文，可读，不拦。
 * 只拦「代码在用 + en 缺失」这一种真会露馅的组合：语言包里另有 380 多个 zh-CN
 * 独有的键从没被引用过，属于历史残留，拦它们只会让这条用例常红。
 */
class TranslationKeyParityTest extends TestCase
{
    /** @test */
    public function every_referenced_key_exists_in_english(): void
    {
        $source = $this->sourceText();
        $missing = [];

        foreach (glob(resource_path('lang/zh-CN/*.php')) as $zhFile) {
            $group  = basename($zhFile, '.php');
            $enFile = resource_path("lang/en/{$group}.php");

            if (! file_exists($enFile)) {
                $missing[] = "整个语言文件缺失: en/{$group}.php";
                continue;
            }

            $zh = $this->flatten(require $zhFile);
            $en = $this->flatten(require $enFile);

            foreach (array_diff_key($zh, $en) as $key => $value) {
                $full = "{$group}.{$key}";

                if (str_contains($source, "'{$full}'") || str_contains($source, "\"{$full}\"")) {
                    $missing[] = "{$full}  (zh-CN: {$value})";
                }
            }
        }

        sort($missing);

        $this->assertSame(
            [],
            $missing,
            "以下翻译键代码里在用，但 en 语言包里没有 —— 切英文时会直接显示 key 名：\n"
            . implode("\n", $missing)
        );
    }

    /**
     * 全部业务源码拼成一段文本，用来判断某个键有没有被引用。
     *
     * 只认字面量。`__('sms.' . $row->status)` 这类拼出来的键静态查不了，
     * 但它们本来也不会因为「zh 有 en 没有」而露馅——那种缺失在两边都缺。
     */
    private function sourceText(): string
    {
        $roots = [app_path(), resource_path('views'), public_path('include_js'), base_path('routes')];
        $text = '';

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if (! preg_match('/\.(php|js)$/', $file->getPathname())) {
                    continue;
                }

                $text .= file_get_contents($file->getPathname());
            }
        }

        return $text;
    }

    /**
     * 嵌套数组压平成点号键，与 __() 的写法对齐。
     */
    private function flatten(array $items, string $prefix = ''): array
    {
        $flat = [];

        foreach ($items as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += $this->flatten($value, $full);
                continue;
            }

            $flat[$full] = $value;
        }

        return $flat;
    }
}
