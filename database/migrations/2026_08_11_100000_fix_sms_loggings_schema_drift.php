<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * sms_loggings 的表结构跟写入代码对不上，导致每条短信日志都写不进去。
 *
 * SmsLogger::LogSms() 写的是：
 *     ['phone_number', 'message', 'cost', 'type' => $type, 'status' => 'pending']
 * 而 2020_06_27_140310 建的表里：
 *     没有 type 列；status 是 enum('success','failed')，不含 'pending'。
 * 中间六年没有任何迁移补过（SmsLogging 模型的 $fillable 里倒是一直写着 'type'）。
 *
 * 后果：每建一条预约都会 dispatch 一个短信任务，任务里这条 INSERT 必然抛
 *     SQLSTATE[42S22] Unknown column 'type'
 * 因为走队列，HTTP 请求不受影响 —— 前台界面一切正常，失败静静躺在 failed_jobs，
 * 病人从来收不到预约确认短信，而且没人会发现。诊所新装的机器（库从迁移建）
 * 必然中招；开发机上的库也确实没有这一列，实测复现。
 *
 * 这里只把表补成代码期待的样子，不动写入侧：
 *   type   → 短标签，现有取值是 Appointment / Reminder
 *   status → 放开到 varchar，短信状态本就会随对接的服务商变化（pending →
 *            sent / delivered / failed），继续用 enum 只会几年后再撞一次同样的墙
 */
class FixSmsLoggingsSchemaDrift extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sms_loggings')) {
            return;
        }

        if (!Schema::hasColumn('sms_loggings', 'type')) {
            Schema::table('sms_loggings', function (Blueprint $table) {
                $table->string('type', 50)->nullable()->after('message');
            });
        }

        DB::statement("ALTER TABLE sms_loggings MODIFY status VARCHAR(20) NULL");
    }

    public function down()
    {
        if (!Schema::hasTable('sms_loggings')) {
            return;
        }

        // 回滚前先把 enum 放不下的值归一，否则 MODIFY 会把它们截成空串
        DB::table('sms_loggings')->whereNotIn('status', ['success', 'failed'])->update(['status' => 'failed']);
        DB::statement("ALTER TABLE sms_loggings MODIFY status ENUM('success','failed') NOT NULL");

        if (Schema::hasColumn('sms_loggings', 'type')) {
            Schema::table('sms_loggings', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
}
