<?php

namespace Tests\Feature;

use App\Http\Helper\SmsLogger;
use App\SmsLogging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * sms_loggings 的表结构曾经跟写入代码对不上六年：SmsLogger::LogSms() 写 type 和
 * status='pending'，而建表迁移里没有 type 列、status 是 enum('success','failed')。
 *
 * 因为短信走队列，这条 INSERT 抛的 SQLSTATE 只会进 failed_jobs —— 页面一切正常，
 * 病人却从来收不到预约确认短信。这组用例把「写得进去」钉死。
 */
class SmsLoggingSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_sms_log_row_can_actually_be_written(): void
    {
        (new SmsLogger())->SendMessage('13800138000', '您的预约已安排', 'Appointment');

        $this->assertDatabaseHas('sms_loggings', [
            'phone_number' => '13800138000',
            'type'         => 'Appointment',
            'status'       => 'pending',
        ]);
    }

    /**
     * status 从 enum 放开成 varchar：短信状态会随对接的服务商变化
     * （pending → sent → delivered / failed），继续用 enum 只会几年后再撞一次。
     */
    /** @test */
    public function status_accepts_values_beyond_the_original_enum(): void
    {
        foreach (['pending', 'sent', 'delivered', 'failed'] as $status) {
            SmsLogging::create([
                'phone_number' => '13900139000',
                'message'      => 'x',
                'cost'         => 0,
                'type'         => 'Reminder',
                'status'       => $status,
            ]);
        }

        $this->assertSame(4, SmsLogging::where('phone_number', '13900139000')->count());
        $this->assertSame(
            'delivered',
            SmsLogging::where('status', 'delivered')->value('status'),
            'status 被截断说明列还是 enum'
        );
    }
}
