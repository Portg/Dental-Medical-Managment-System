<?php

namespace App\Http\Helper;

use App\SmsLogging;
use Illuminate\Support\Facades\Log;

class SmsLogger
{
    public function __construct()
    {
        //
    }

    /**
     * 未配置服务商时的落库状态。
     *
     * 不要写 'pending'：那是「已交给服务商、等回执」的意思，而这里根本没有服务商，
     * 状态永远不会再变。短信记录页与导出都直出这个字段，前台看到一列 pending 会
     * 理解成在排队，于是没人去补打电话——预约提醒就这么静悄悄地没发出去。
     */
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    /**
     * Send SMS message.
     *
     * 接入国内短信服务（阿里云/腾讯云）时替换此占位实现：真正发出后按服务商回执
     * 写 sent / delivered / failed，届时本类的 STATUS_NOT_CONFIGURED 自然退场。
     */
    public function SendMessage($phone_number, $message, $type)
    {
        Log::info('SMS not sent (no provider configured)', [
            'phone' => $phone_number,
            'type' => $type,
            'message' => $message,
        ]);

        // Log the attempt
        $this->LogSms($phone_number, $message, $type);
    }

    private function LogSms($phone_number, $message, $type)
    {
        SmsLogging::create([
            'phone_number' => $phone_number,
            'message' => $message,
            'cost' => '0',
            'type' => $type,
            'status' => self::STATUS_NOT_CONFIGURED,
        ]);
    }
}
