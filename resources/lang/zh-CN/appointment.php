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
    'appointment_management' => '预约管理',
    'appointment_list' => '预约列表',
    'appointment_details' => '预约详情',
    'add_appointment' => '添加预约',
    'edit_appointment' => '编辑预约',
    'view_appointment' => '查看预约',
    'book_appointment' => '预约挂号',
    'appointment_calendar' => '预约日历',
    'online_bookings' => '在线预约',
    'appointment_schedule' => '预约日程',

    // Appointment Information
    'appointment_id' => '预约编号',
    'appointment_number' => '预约号',
    'appointment_date' => '预约日期',
    'appointment_time' => '预约时间',
    'appointment_type' => '预约类型',
    'appointment_status' => '预约状态',
    'appointment_reason' => '预约原因',
    'appointment_notes' => '预约备注',
    'duration' => '时长',
    'estimated_duration' => '预计时长',

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
    'select_patient' => '选择患者',
    'select_doctor' => '选择医生',

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
    'reschedule_appointment' => '改约',
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

    // Other
    'waiting_time' => '等待时间',
    'consultation_room' => '诊室',
    'room_number' => '房间号',
    'queue_number' => '排队号',
    'priority' => '优先级',
    'high_priority' => '高优先级',
    'normal_priority' => '普通优先级',
    'low_priority' => '低优先级',

];
