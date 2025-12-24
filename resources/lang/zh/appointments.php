<?php

return [
    // 页面标题
    'title' => '预约管理',
    'online_bookings' => '预约 / 在线预约',
    'appointments_calendar' => '预约日历',

    // 表头
    'table' => [
        'appointment_no' => '预约编号',
        'appointment_date' => '预约日期',
        'appointment_time' => '预约时间',
        'patient' => '患者',
        'doctor' => '医生',
        'appointment_category' => '预约类别',
        'invoice_status' => '发票状态',
        'booking_date' => '预订日期',
        'preferred_date' => '首选预约日期',
        'preferred_time' => '首选预约时间',
        'is_new_patient' => '是否新患者',
    ],

    // 表单
    'form' => [
        'title' => '预约表单',
        'patient' => '患者',
        'doctor' => '医生',
        'visit_info' => '就诊信息',
        'appointment_date' => '预约日期',
        'appointment_time' => '预约时间',
        'general_notes' => '一般备注（可选）',
        'appointment_status' => '预约状态',
        'walk_in' => '现场挂号',
        'appointment' => '预约',
        'select_action' => '选择预约操作',
        'treatment_complete' => '治疗完成',
        'treatment_incomplete' => '治疗未完成',
    ],

    // 筛选
    'filters' => [
        'filter_appointments' => '筛选预约',
        'filter_bookings' => '筛选预订',
    ],

    // 在线预约
    'booking' => [
        'phone_no' => '电话号码',
        'email' => '电子邮件',
        'accept_booking' => '接受预约',
        'reject_booking' => '拒绝预约',
        'approving' => '批准预约中...',
        'accept_confirm' => '您确定吗？您要接受此预约！',
        'reject_confirm' => '您确定吗？您要拒绝此预约',
    ],

    // 提示
    'alerts' => [
        'delete_confirm' => '您将无法恢复此预约！',
    ],

    // 仪表板
    'dashboard' => [
        'todays_appointments' => '今日预约',
    ],

    // 通知
    'notifications' => [
        'system_sent' => '系统发送的通知',
        'sms_reminders' => '预约短信提醒',
    ],
];
