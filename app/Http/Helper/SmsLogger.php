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
     * Send SMS message.
     *
     * TODO: 接入国内短信服务（阿里云/腾讯云）替换此占位实现
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
            'status' => 'pending'
        ]);
    }
}
