<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ocr' => [
        // Win7 部署时，若目标机无法运行 paddlepaddle（常见于不支持 AVX 指令集的
        // 老 CPU），install-win.ps1 会写入 OCR_ENABLED=false，此时识别功能整体
        // 关闭，工作日志回落为手工录入。
        'enabled' => filter_var(env('OCR_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'python_path' => env('OCR_PYTHON_PATH', 'python3'),
        'script_path' => env('OCR_SCRIPT_PATH', base_path('scripts/ocr_service.py')),
        'worklog_script_path' => env('OCR_WORKLOG_SCRIPT_PATH', base_path('scripts/worklog_ocr.py')),
        'timeout' => env('OCR_TIMEOUT', 60),
        'server_url' => env('OCR_SERVER_URL', 'http://127.0.0.1:5000'),
    ],

];
