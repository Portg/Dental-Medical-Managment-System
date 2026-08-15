<?php

use App\Http\Helper\SmsLogger;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 把历史上那些永远不会发出的短信记录改标成 not_configured。
 *
 * SmsLogger 从来没有接过服务商，每条预约提醒/生日祝福都往 sms_loggings 写一行
 * status='pending' 就结束了。但 'pending' 的含义是「已交给服务商、等回执」——
 * 短信记录页直出这个字段，前台看到一列「待处理」会以为在排队，于是不会去补打
 * 电话确认。这些行的真实状态是「压根没发」，改标成 not_configured 才对得上。
 *
 * 只动 SmsLogger 自己写的那批（cost='0' 且 status='pending'）。真接了服务商之后
 * 产生的 pending 是合法中间态，不能一起改。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sms_loggings')
            ->where('status', 'pending')
            ->where('cost', '0')
            ->update(['status' => SmsLogger::STATUS_NOT_CONFIGURED]);
    }

    public function down(): void
    {
        DB::table('sms_loggings')
            ->where('status', SmsLogger::STATUS_NOT_CONFIGURED)
            ->update(['status' => 'pending']);
    }
};
