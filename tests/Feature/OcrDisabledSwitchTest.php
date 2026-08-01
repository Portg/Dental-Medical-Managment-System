<?php

namespace Tests\Feature;

use App\Console\Commands\OcrServeCommand;
use App\Services\OcrService;
use App\Services\WorkLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OCR 总开关（services.ocr.enabled）。
 *
 * Windows 7 目标机若 CPU 不支持 AVX，paddlepaddle 无法加载，安装脚本会把
 * .env 的 OCR_ENABLED 置为 false。此时所有 OCR 入口都必须提前返回可读提示，
 * 而不是去启动注定崩溃的 Python 子进程、把底层报错抛给用户。
 *
 * 共有三个入口，任何一个漏判都会让降级失效：
 *   - OcrService::recognize()        病历图片识别
 *   - WorkLogService::recognize()    工作日志表格识别（不经过 OcrService）
 *   - OcrServeCommand                常驻 OCR 服务
 */
class OcrDisabledSwitchTest extends TestCase
{
    use RefreshDatabase;

    /** 造一个真实存在的图片路径，确保用例失败时不是因为文件不存在 */
    private function existingImagePath(): string
    {
        return __FILE__;
    }

    public function test_ocr_service_rejects_when_disabled(): void
    {
        config(['services.ocr.enabled' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('work_log.ocr_disabled'));

        app(OcrService::class)->recognize($this->existingImagePath());
    }

    public function test_worklog_service_rejects_when_disabled(): void
    {
        config(['services.ocr.enabled' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(__('work_log.ocr_disabled'));

        app(WorkLogService::class)->recognize($this->existingImagePath());
    }

    public function test_ocr_serve_command_exits_cleanly_when_disabled(): void
    {
        config(['services.ocr.enabled' => false]);

        $this->artisan('ocr:serve')->assertExitCode(0);
    }

    public function test_switch_defaults_to_enabled(): void
    {
        // .env 未设置 OCR_ENABLED 时必须默认开启，避免误伤 Win10+ 的正常部署。
        // 每个用例都会重新引导应用，这里读到的就是 config/services.php 的默认值。
        $this->assertTrue(config('services.ocr.enabled'));
        $this->assertIsBool(config('services.ocr.enabled'));
    }

    public function test_disabled_switch_does_not_spawn_python(): void
    {
        config(['services.ocr.enabled' => false]);
        // 指向一个不存在的解释器：若开关失效而去起子进程，抛出的会是
        // ProcessFailedException / 找不到可执行文件，而不是我们的提示语。
        config(['services.ocr.python_path' => '/nonexistent/python-should-never-run']);

        try {
            app(WorkLogService::class)->recognize($this->existingImagePath());
            $this->fail('OCR 关闭时 recognize() 应当抛出 RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame(__('work_log.ocr_disabled'), $e->getMessage());
        }
    }
}
