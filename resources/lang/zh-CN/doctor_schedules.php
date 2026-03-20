<?php

return [
    'title' => '医生排班',
    'schedule_form' => '排班表单',
    'list_view' => '列表视图',
    'calendar_view' => '日历视图',
    'grid_view' => '排班网格',
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

    // Weekdays
    'mon' => '一',
    'tue' => '二',
    'wed' => '三',
    'thu' => '四',
    'fri' => '五',
    'sat' => '六',
    'sun' => '日',

    // Grid
    'shift_buttons_label' => '班次',
    'click_to_assign' => '点击班次按钮后，再点击表格中的格子即可分配排班',
    'no_schedules' => '本月暂无排班',
    'legacy_shift' => '旧排班',
    'already_assigned' => '该班次已分配',
    'not_found' => '排班记录不存在',

    // Conflict
    'time_conflict' => '与已有班次":shift"(:time)时间冲突',

    // Copy
    'copy_week' => '复制排班',
    'copy_month' => '复制上月排班',
    'source_week' => '使用',
    'source_week_suffix' => '所在周的排班表',
    'copy_to' => '复制到',
    'copy_confirm' => '确定',
    'source_week_empty' => '源周没有排班数据',
    'source_month_empty' => '上月没有排班数据',
    'copy_week_success' => '已成功复制 :count 条排班',
    'copy_month_success' => '已成功复制 :count 条排班',
    'copy_failed' => '复制失败，请重试',

    // Permission
    'cannot_edit_others' => '您只能编辑自己的排班',
    'cannot_delete_past' => '无法删除当天或已过日期的排班',
    'has_linked_appointments' => '该排班关联 :count 个预约，无法删除。请先取消或改期相关预约',

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

    // Export
    'export' => '导出排班表',

    // AG-073: Waiting queue guard
    'delete_has_waiting_patients' => '该排班日当天有 :count 名患者正在等候或就诊中，无法删除',

    // Shift settings
    'shift_settings' => '班次设置',
];
