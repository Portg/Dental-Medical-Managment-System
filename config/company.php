<?php

/*
 * 诊所抬头信息（名称、地址、联系方式、主诊医生）。
 *
 * 这些值原本散在 8 个视图里直接 env() 读取。Laravel 在执行
 * `php artisan config:cache` 之后不再加载 .env，config 之外的 env() 一律返回
 * 默认值 —— 而 Windows 安装脚本每次部署都会跑 config:cache。结果就是页脚回落成
 * 英文兜底串「Dental Medical System」，发票、报价单、打印抬头上的诊所地址、
 * 电话、邮箱、税号直接变空白，且只在生产机上复现，本地永远看不到。
 *
 * 收进 config 之后 env() 只在这里出现，config:cache 反而成了安全的。
 *
 * 诊所名默认回落到 APP_NAME，避免 .env 没配 CompanyName 时冒出英文串。
 */

return [
    'name'    => env('CompanyName', env('APP_NAME', 'Dental Medical System')),
    'address' => env('CompanyAddress'),
    'tin_no'  => env('companyTinNo'),

    /*
     * 中文抬头覆盖：CompanyName / CompanyAddress 是给英文单据用的，
     * 中文单据（resources/lang/zh-CN/company.php）优先取这两项，没配就回落到上面。
     * 原先这两个 key 被 en/company.php 也照抄了一遍，导致英文发票印出中文抬头。
     */
    'name_zh'    => env('COMPANY_NAME_ZH'),
    'address_zh' => env('COMPANY_ADDRESS_ZH'),

    'email' => [
        // 对外正式邮箱（发票、报价单落款）
        'official' => env('companyOfficalEmail'),
        // 咨询邮箱（打印抬头）
        'info'     => env('companyInfoEmail'),
        // 系统外发邮件的 no-reply 地址
        'no_reply' => env('CompanyNoReplyEmail'),
    ],

    'mobile'       => env('companyMobile'),
    'mobile_other' => env('companyMobileOther'),

    'main_doctor' => [
        'name'     => env('MainDoctorName'),
        'contacts' => env('mainDoctorContacts'),
    ],
];
