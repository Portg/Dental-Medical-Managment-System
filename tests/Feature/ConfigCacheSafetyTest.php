<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Laravel 在 `php artisan config:cache` 之后不再加载 .env，config 目录之外的 env()
 * 一律拿不到值（只会返回第二个参数，没写就是 null）。而 Windows 安装脚本每次部署
 * 都会跑 config:cache。
 *
 * 这个坑之前真的踩了：页脚、发票抬头、打印单的诊所名称/地址/电话/邮箱/税号全都写成
 * {{ env('CompanyName', 'Dental Medical System') }} 这种形式，本地开发看着好好的，
 * 一到诊所机器上就回落成英文兜底串和空白。改成 config('company.*') 之后，
 * 这组用例负责挡住下一次手滑。
 */
class ConfigCacheSafetyTest extends TestCase
{
    /** @test */
    public function views_and_app_code_do_not_call_env_directly(): void
    {
        $offenders = [];

        foreach (['resources', 'app'] as $dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                // 匹配 env( 但放过 ->env( / getenv( 之类的方法调用
                if (preg_match('/(?<![\w>$])env\s*\(/', $contents)) {
                    $offenders[] = str_replace(base_path() . '/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "config:cache 之后这些文件里的 env() 会拿不到值，请把配置收进 config/ 再用 config()：\n"
            . implode("\n", $offenders)
        );
    }

    /** @test */
    public function company_name_never_falls_back_to_an_english_placeholder(): void
    {
        $this->assertNotEmpty(config('company.name'), '诊所名称为空，页脚和单据抬头会开天窗');

        // 没配 CompanyName 时应当回落到 APP_NAME，而不是英文兜底串
        $this->assertNotSame(
            'Dental Medical System',
            config('company.name'),
            'CompanyName 未配置时应回落到 APP_NAME'
        );
    }

    /**
     * en/company.php 曾经照抄 zh-CN 的 COMPANY_NAME_ZH / COMPANY_ADDRESS_ZH 覆盖项，
     * 结果英文单据印出中文抬头。
     */
    /** @test */
    public function english_company_letterhead_does_not_use_the_chinese_override(): void
    {
        $en = file_get_contents(resource_path('lang/en/company.php'));

        $this->assertStringNotContainsString('company.name_zh', $en);
        $this->assertStringNotContainsString('company.address_zh', $en);
    }
}
