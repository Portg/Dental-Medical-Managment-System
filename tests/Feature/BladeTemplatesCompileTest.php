<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `php artisan view:cache` 只保证 Blade 指令能被翻译成 PHP，**不检查翻译结果是不是合法
 * PHP**。所以一个 `{{ $case- }}` 这样被截断的表达式能顺利通过 view:cache，直到用户打开
 * 那个页面才炸出 ParseError —— 而且只炸那一个页面，跑测试、看首页都发现不了。
 *
 * 这条用例把全部模板编译一遍，再逐个用 TOKEN_PARSE 解析编译产物：语法不合法就抛
 * ParseError。等于把「打开每个页面」压缩成一次断言。
 */
class BladeTemplatesCompileTest extends TestCase
{
    /** @test */
    public function every_blade_template_compiles_to_valid_php(): void
    {
        Artisan::call('view:clear');
        Artisan::call('view:cache');

        $compiled = File::glob(storage_path('framework/views/*.php'));

        $this->assertNotEmpty($compiled, 'view:cache 没有产出任何编译文件，用例本身失效了');

        $broken = [];

        foreach ($compiled as $path) {
            $source = file_get_contents($path);

            try {
                // TOKEN_PARSE 会做完整语法校验，非法语法抛 ParseError
                token_get_all($source, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $broken[] = $this->originalTemplate($source) . ' — ' . $e->getMessage();
            }
        }

        $this->assertSame(
            [],
            $broken,
            "以下模板编译出的 PHP 语法非法，打开对应页面会直接 500：\n" . implode("\n", $broken)
        );
    }

    /**
     * 编译产物首行是 `<?php /**PATH /abs/path/to/view.blade.php ENDPATH** /?>`，
     * 从里面取回源模板路径，报错信息才有意义。
     */
    private function originalTemplate(string $compiledSource): string
    {
        if (preg_match('/PATH\s+(.+?)\s+ENDPATH/', $compiledSource, $m)) {
            return str_replace(base_path() . '/', '', $m[1]);
        }

        return '(未能解析出源模板路径)';
    }
}
