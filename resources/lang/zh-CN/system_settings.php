<?php

return [
    'page_title' => '系统设置',

    // Tabs
    'tab_clinic' => '诊所设置',
    'tab_member' => '会员设置',

    // Messages
    'saved' => '设置已保存',
    'invalid_group' => '无效的设置分组',

    // ── Clinic settings ─────────────────────────────────────────
    'clinic_time_range' => '预约时间范围',
    'clinic_start_time' => '起始时间',
    'clinic_end_time' => '结束时间',
    'clinic_slot_interval' => '时段间隔',
    'clinic_slot_interval_hint' => '预约时段划分间隔（分钟）',
    'clinic_default_duration' => '默认预约时长',
    'clinic_default_duration_hint' => '新建预约时的默认时长（分钟）',
    'clinic_grid_start_hour' => '网格起始小时',
    'clinic_grid_end_hour' => '网格结束小时',
    'clinic_grid_range_hint' => '医生日视图网格的显示时间范围',
    'clinic_display_settings' => '预约显示设置',
    'clinic_hide_off_duty_doctors' => '不显示休息医生',
    'clinic_hide_off_duty_doctors_hint' => '预约中心隐藏当日无排班的医生',
    'clinic_show_appointment_notes' => '显示预约备注',
    'clinic_show_appointment_notes_hint' => '在日历和列表中显示预约备注内容',
    'clinic_rules' => '预约规则',
    'clinic_allow_overbooking' => '允许重复预约',
    'clinic_allow_overbooking_hint' => '允许同一时段创建多个预约',
    'clinic_max_advance_days' => '最大提前预约天数',
    'clinic_max_advance_days_hint' => '患者可提前预约的最大天数（0=不限制）',
    'clinic_min_advance_hours' => '最少提前预约小时数',
    'clinic_min_advance_hours_hint' => '预约时间需至少提前的小时数（0=不限制）',
    'minutes' => '分钟',
    'hours' => '小时',
    'days' => '天',

    // ── Member settings (labels for the unified page) ───────────
    'member_points_enabled' => '积分功能',
    'member_points_enabled_hint' => '全局开启或关闭积分功能',
    'member_points_expiry_days' => '积分有效天数',
    'member_points_expiry_days_hint' => '积分到期天数（0=永不过期）',
    'member_card_number_mode' => '卡号生成模式',
    'member_card_mode_auto' => '自动生成',
    'member_card_mode_phone' => '使用手机号',
    'member_card_mode_manual' => '手动输入',
    'member_referral_bonus_enabled' => '推荐开卡奖励',
    'member_referral_bonus_enabled_hint' => '老会员推荐新会员是否赠送积分',
    'member_points_exchange_rate' => '积分兑换比例',
    'member_points_exchange_rate_hint' => 'X 积分兑换 1 元',
    'member_points_exchange_enabled' => '积分兑换功能',
    'member_points_exchange_enabled_hint' => '是否允许会员用积分兑换余额',
];
