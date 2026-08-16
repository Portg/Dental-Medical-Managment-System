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
        $referenced = $this->referencedKeys();
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

                if (isset($referenced[$full])) {
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
     * 业务源码里以字面量形式出现的翻译键，收成一张 key => true 的表。
     *
     * 只认字面量。`__('sms.' . $row->status)` 这类拼出来的键静态查不了，
     * 但它们本来也不会因为「zh 有 en 没有」而露馅——那种缺失在两边都缺。
     *
     * 原先是把全部源码（约 5MB）拼成一个字符串再 str_contains。那已经贴着
     * memory_limit 128M 的天花板了：字符串扩容时要同时持有新旧两份，源码再多几百
     * 字节就整片 OOM，还报在这条用例上，看不出跟改了什么有关。改成逐文件正则提取，
     * 峰值只跟单个文件大小走。
     *
     * @return array<string, true>
     */
    private function referencedKeys(): array
    {
        $roots = [app_path(), resource_path('views'), public_path('include_js'), base_path('routes')];
        $keys = [];

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

                $contents = file_get_contents($file->getPathname());

                // 'group.key' / "group.key"，与原先 str_contains 的判据一致
                if (preg_match_all('/[\'"]([a-z0-9_]+(?:\.[a-zA-Z0-9_-]+)+)[\'"]/', $contents, $matches)) {
                    foreach ($matches[1] as $key) {
                        $keys[$key] = true;
                    }
                }

                unset($contents);
            }
        }

        return $keys;
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
