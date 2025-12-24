<?php

return [
    // 页面标题
    'title' => '发票 / 账单',
    'quotations' => '账单 / 报价单',

    // 表头
    'table' => [
        'invoice_no' => '发票编号',
        'quotation_no' => '报价单编号',
        'date' => '日期',
        'customer' => '客户',
        'total_amount' => '总金额',
        'paid_amount' => '已付金额',
        'outstanding' => '未付款',
    ],

    // 按钮
    'buttons' => [
        'filter_invoices' => '筛选发票',
        'filter_quotations' => '筛选报价单',
        'share_invoice' => '分享发票',
        'share_quotation' => '分享报价单',
        'print' => '打印',
        'preview' => '预览',
    ],

    // 付款表单
    'payment' => [
        'title' => '记录此发票的付款',
        'payment_date' => '付款日期',
        'amount' => '金额',
        'payment_method' => '付款方式',
        'methods' => [
            'cash' => '现金',
            'insurance' => '保险',
            'online_wallet' => '在线钱包',
            'mobile_money' => '移动支付',
            'cheque' => '支票',
            'self_account' => '自费账户',
        ],
        'cheque_no' => '支票号码',
        'account_name' => '账户名称',
        'bank_name' => '银行名称',
    ],

    // 仪表板
    'dashboard' => [
        'todays_cash' => '今日现金（金额）',
        'todays_insurance' => '今日保险（金额）',
    ],

    // 通知
    'notifications' => [
        'email_sent' => '已发送电子邮件发票/报价单',
    ],

    // 提示
    'alerts' => [
        'delete_confirm' => '您将无法恢复此发票！',
    ],
];
