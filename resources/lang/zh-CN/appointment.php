<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Appointment Management Language Lines
    |--------------------------------------------------------------------------
    |
    | Language lines for appointment management module
    |
    */

    // Page Titles
    'appointments' => '预约',
    'appointment_mgt' => '预约管理',
    'appointment_list' => '预约列表',
    'appointment_details' => '预约详情',
    'add_appointment' => '添加预约',
    'edit_appointment' => '编辑预约',
    'view_appointment' => '查看预约',
    'book_appointment' => '预约挂号',
    'appointment_calendar' => '预约日历',
    'online_bookings' => '在线预约',
    'appointment_schedule' => '预约日程',
    'appointment_form' => '预约表单',
    'appointments_calender' => '预约日历',

    // Appointment Information
    'appointment_no' => '预约编号',
    'appointment_number' => '预约号',
    'appointment_date' => '预约日期',
    'appointment_time' => '预约时间',
    'appointment_category' => '预约类型',
    'invoice_status' => '预约状态',
    'date' => '预约日期',
    'status' => '状态',
    'appointment_reason' => '预约原因',
    'appointment_notes' => '预约备注',
    'duration' => '时长',
    'estimated_duration' => '预计时长',
    'visit_information' => '就诊信息',
    'walk_in' => '临时就诊',
    'appointment' => '预约',

    // Appointment Types
    'consultation' => '咨询',
    'checkup' => '检查',
    'treatment' => '治疗',
    'followup' => '复诊',
    'follow_up' => '复诊',
    'emergency' => '急诊',
    'surgery' => '手术',
    'routine' => '常规',
    'new_patient' => '新患者',
    'existing_patient' => '现有患者',

    // Appointment Status
    'pending' => '待处理',
    'confirmed' => '已确认',
    'scheduled' => '已预约',
    'arrived' => '已到达',
    'in_progress' => '进行中',
    'completed' => '已完成',
    'cancelled' => '已取消',
    'no_show' => '未到',
    'rescheduled' => '已改约',
    'waiting' => '等待中',

    // Patient & Doctor
    'patient' => '患者',
    'patient_name' => '患者姓名',
    'patient_id' => '患者编号',
    'doctor' => '医生',
    'doctor_name' => '医生姓名',
    'assigned_to' => '分配给',
    'choose_patient' => '选择患者',
    'choose_doctor' => '选择医生',
    'select_procedure' => '选择诊疗项目',
    'procedure_done_by' => '诊疗医生...',
    'reactivate_appointment' => '激活预约',
    're_activate_appointment' => '重新激活预约',
    'reschedule_appointment' => '重新安排预约',

    // Time Slots
    'time_slot' => '时间段',
    'available_slots' => '可用时间段',
    'no_available_slots' => '没有可用时间段',
    'select_time_slot' => '选择时间段',
    'morning' => '上午',
    'afternoon' => '下午',
    'evening' => '晚上',

    // Booking
    'book_now' => '立即预约',
    'book_for_patient' => '为患者预约',
    'booking_date' => '预约日期',
    'booking_time' => '预约时间',
    'booking_details' => '预约详情',
    'booking_confirmation' => '预约确认',
    'booking_number' => '预约号',

    // Online Booking
    'online_booking' => '在线预约',
    'online_appointment' => '在线预约',
    'book_online' => '在线预约',
    'online_booking_form' => '在线预约表单',
    'submit_booking' => '提交预约',

    // Actions
    'confirm_appointment' => '确认预约',
    'cancel_appointment' => '取消预约',
    'mark_as_completed' => '标记为已完成',
    'mark_as_arrived' => '标记为已到达',
    'mark_as_no_show' => '标记为未到',
    'send_reminder' => '发送提醒',
    'check_in' => '签到',
    'check_out' => '签退',

    // Calendar
    'calendar' => '日历',
    'today' => '今天',
    'week' => '周',
    'month' => '月',
    'day' => '日',
    'list' => '列表',
    'agenda' => '日程',

    // Notifications & Reminders
    'reminder' => '提醒',
    'send_reminder_sms' => '发送短信提醒',
    'send_reminder_email' => '发送邮件提醒',
    'reminder_sent' => '提醒已发送',
    'appointment_reminder' => '预约提醒',
    'reminder_before' => '提前提醒',
    'hours_before' => '小时前',
    'days_before' => '天前',

    // Search & Filter
    'search_appointments' => '搜索预约',
    'filter_by_date' => '按日期筛选',
    'filter_by_status' => '按状态筛选',
    'filter_by_doctor' => '按医生筛选',
    'filter_by_patient' => '按患者筛选',
    'filter_by_type' => '按类型筛选',
    'show_all' => '显示全部',
    'upcoming' => '即将到来',
    'past' => '过去',
    'today_appointments' => '今日预约',
    'tomorrow_appointments' => '明日预约',
    'this_week_appointments' => '本周预约',

    // Messages
    'appointment_created_successfully' => '预约创建成功！',
    'appointment_updated_successfully' => '预约更新成功！',
    'appointment_deleted_successfully' => '预约删除成功！',
    'appointment_confirmed_successfully' => '预约确认成功！',
    'appointment_cancelled_successfully' => '预约取消成功！',
    'appointment_rescheduled_successfully' => '改约成功！',
    'appointment_not_found' => '未找到预约。',
    'confirm_delete_appointment' => '您确定要删除此预约吗？',
    'confirm_cancel_appointment' => '您确定要取消此预约吗？',
    'error_creating_appointment' => '创建预约时出错，请重试。',
    'error_updating_appointment' => '更新预约时出错，请重试。',
    'error_deleting_appointment' => '删除预约时出错，请重试。',
    'no_appointments_found' => '未找到预约。',
    'slot_not_available' => '该时间段不可用。',
    'doctor_not_available' => '医生在此时间不可用。',
    'appointment_conflict' => '预约时间冲突。',
    'past_date_not_allowed' => '不能预约过去的日期。',
    'booking_submitted_successfully' => '预约提交成功！我们会尽快与您联系确认。',
    'invalid_appointment_time' => '无效的预约时间。',
    'appointment_already_exists' => '该时间段已有预约。',
    'delete_appointment_warning' => '删除预约将无法恢复，请谨慎操作。',

    // Validation
    'patient_required' => '请选择患者。',
    'doctor_required' => '请选择医生。',
    'date_required' => '预约日期为必填项。',
    'time_required' => '预约时间为必填项。',
    'type_required' => '预约类型为必填项。',
    'reason_required' => '预约原因为必填项。',

    // Statistics
    'total_appointments' => '总预约数',
    'today_total' => '今日总数',
    'pending_appointments' => '待处理预约',
    'confirmed_appointments' => '已确认预约',
    'completed_appointments' => '已完成预约',
    'cancelled_appointments' => '已取消预约',
    'no_show_appointments' => '未到预约',

    // Notes
    'chief_complaint' => '主诉',
    'clinical_notes' => '临床备注',
    'treatment_notes' => '治疗备注',
    'add_notes' => '添加备注',
    'general_notes' => '一般备注',
    'generate_invoice' => '生成发票',
    'enter_tooth_number' => '输入牙齿编号',
    'enter_qty' => '输入数量',
    'enter_unit_price' => '输入单价',

    // Other
    'waiting_time' => '等待时间',
    'consultation_room' => '诊室',
    'room_number' => '房间号',
    'queue_number' => '排队号',
    'priority' => '优先级',
    'high_priority' => '高优先级',
    'normal_priority' => '普通优先级',
    'low_priority' => '低优先级',

    'filter_appointments' => '过滤预约',
    'enter_appointment_no' => '输入预约编号',
    'enter_general_notes' => '在此输入一般备注（如有）',
    'optional' => '可选',
    'reschedule' => '改约',

    // Form section headers (design spec)
    'patient_info' => '患者与医生',
    'visit_info' => '就诊信息',
    'schedule_info' => '预约时间',

    // Search and filters
    'quick_search_placeholder' => '搜索患者、电话、预约编号...',
    'invoiced' => '已开票',
    'appointment_status' => '预约状态',

    // Design spec F-APT-001: New appointment form
    'new_appointment' => '新建预约',
    'search_patient_placeholder' => '输入姓名/手机号搜索',
    'create_new_patient' => '+ 新建患者',
    'selected_patient_info' => '已选患者信息',
    'last_visit' => '最近就诊',
    'allergy_label' => '过敏',
    'no_allergy' => '无',
    'select_date' => '预约日期',
    'select_doctor' => '医生',
    'time_slot_selection' => '时段选择',
    'available' => '可选',
    'selected' => '选中',
    'booked' => '已约',
    'rest_time' => '休息',
    'chair_selection' => '椅位',
    'auto_assign' => '自动分配',
    'appointment_service' => '预约项目',
    'visit_type' => '就诊类型',
    'first_visit' => '初诊',
    'revisit' => '复诊',
    'estimated_duration_label' => '预计时长',
    'minutes' => '分钟',
    'confirm_appointment' => '确认预约',
    'patient_has_appointment_today' => '该患者当天已有预约，是否查看？',
    'patient_no_show_warning' => '该患者连续爽约:count次，请注意',
    'slot_occupied_by' => '已被预约：',
    'consecutive_slots_required' => '所选时长需要连续:count个时段，请选择连续可用时段',
    'chair_optional_hint' => '可选，不选则系统分配',
    'weekday_mon' => '周一',
    'weekday_tue' => '周二',
    'weekday_wed' => '周三',
    'weekday_thu' => '周四',
    'weekday_fri' => '周五',
    'weekday_sat' => '周六',
    'weekday_sun' => '周日',
    'notes' => '备注',
    'no_available_slots' => '暂无可用时段',

    'past_slot' => '已过',
    'all_slots_past' => '当天所有时段已过，请选择其他日期',

    // Validation messages
    'patient_required' => '请选择患者',
    'doctor_required' => '请选择医生',
    'date_required' => '请选择预约日期',
    'time_required' => '请选择预约时间',

    // Popover (calendar event card)
    'popover_time' => '时间',
    'popover_project' => '项目',
    'popover_status' => '状态',
    'popover_send_sms' => '短信',
    'doctor_day_view' => '医生日视图',
    'no_appointments' => '暂无预约',
    'doctor_no_schedule_warning' => '该医生当日无排班记录，显示的为默认时段',
    'no_phone_for_sms' => '该患者无手机号，无法发送短信',

    // Resource grid schedule
    'no_schedule' => '未排班',
    'off_schedule_warning' => '该时段不在医生排班范围内',

    // Overbooking
    'overbooking_conflict' => '该医生在所选时段已有预约，时间冲突，请选择其他时间',

    // Advance booking limits
    'max_advance_days_exceeded' => '最多只能提前 :days 天预约',
    'min_advance_hours_not_met' => '至少需要提前 :hours 小时预约',
];