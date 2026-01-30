<?php

return [
    'title' => '医生排班',
    'schedule_form' => '排班表单',
    'list_view' => '列表视图',
    'calendar_view' => '日历视图',
    'all_doctors' => '所有医生',

    // Fields
    'doctor' => '医生',
    'date' => '日期',
    'start_time' => '开始时间',
    'end_time' => '结束时间',
    'time_range' => '时间段',
    'max_patients' => '最大接诊数',
    'branch' => '分院',
    'notes' => '备注',
    'recurring' => '重复',
    'enable_recurring' => '启用重复排班',
    'recurring_pattern' => '重复模式',
    'recurring_until' => '重复至',

    // Patterns
    'pattern_daily' => '每日',
    'pattern_weekly' => '每周',
    'pattern_monthly' => '每月',

    // Placeholders
    'select_doctor' => '选择医生',
    'select_branch' => '选择分院',

    // Validation
    'doctor_required' => '请选择医生',
    'date_required' => '请输入排班日期',
    'start_time_required' => '请输入开始时间',
    'end_time_required' => '请输入结束时间',
    'end_time_after_start' => '结束时间必须晚于开始时间',
    'max_patients_required' => '请输入最大接诊数',

    // Messages
    'added_successfully' => '排班添加成功',
    'updated_successfully' => '排班更新成功',
    'deleted_successfully' => '排班删除成功',
    'delete_confirm' => '确定要删除此排班吗？',
];
