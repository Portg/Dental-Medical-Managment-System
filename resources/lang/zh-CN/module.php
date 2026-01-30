<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module Specific Language Lines
    |--------------------------------------------------------------------------
    |
    | Language lines for different modules (Doctor, Nurse, Receptionist, etc.)
    |
    */

    // Modules
    'super_admin' => '超级管理员',
    'doctor' => '医生',
    'nurse' => '护士',
    'receptionist' => '前台',
    'pharmacist' => '药剂师',

    // Super Admin
    'super_admin_dashboard' => '超级管理员仪表板',
    'system_overview' => '系统概览',
    'user_management' => '用户管理',
    'role_management' => '角色管理',
    'system_configuration' => '系统配置',
    'database_management' => '数据库管理',
    'backup_management' => '备份管理',

    // Doctor Module
    'doctor_dashboard' => '医生仪表板',
    'my_appointments' => '我的预约',
    'my_patients' => '我的患者',
    'my_schedule' => '我的日程',
    'consultation_queue' => '诊疗队列',
    'patient_consultations' => '患者诊疗',
    'treatment_plans' => '治疗计划',
    'prescriptions' => '处方',
    'medical_records' => '医疗记录',
    'doctor_claims' => '医生理赔',
    'performance_reports' => '绩效报表',
    'upcoming_appointments' => '即将到来的预约',
    'today_schedule' => '今日日程',

    // Nurse Module
    'nurse_dashboard' => '护士仪表板',
    'patient_care' => '患者护理',
    'vital_signs' => '生命体征',
    'nursing_notes' => '护理备注',
    'medication_administration' => '给药管理',
    'patient_monitoring' => '患者监护',
    'ward_management' => '病房管理',
    'triage' => '分诊',
    'patient_admission' => '患者入院',
    'patient_discharge' => '患者出院',

    // Receptionist Module
    'receptionist_dashboard' => '前台仪表板',
    'patient_registration' => '患者登记',
    'appointment_scheduling' => '预约安排',
    'check_in' => '签到',
    'check_out' => '签退',
    'waiting_list' => '等候名单',
    'front_desk' => '前台',
    'visitor_management' => '访客管理',
    'phone_directory' => '电话簿',

    // Pharmacy Module
    'pharmacy_dashboard' => '药房仪表板',
    'pharmacy' => '药房',
    'medications' => '药品',
    'drug_inventory' => '药品库存',
    'stock_management' => '库存管理',
    'drug_dispensing' => '配药',
    'prescription_filling' => '处方配药',
    'inventory_alerts' => '库存提醒',
    'expired_drugs' => '过期药品',
    'drug_orders' => '药品订单',
    'suppliers' => '供应商',
    'purchase_orders' => '采购订单',
    'stock_in' => '入库',
    'stock_out' => '出库',
    'drug_categories' => '药品分类',
    'reorder_level' => '补货水平',
    'minimum_stock' => '最小库存',
    'current_stock' => '当前库存',
    'low_stock' => '低库存',
    'out_of_stock' => '缺货',

    // HR Module
    'hr_dashboard' => '人力资源仪表板',
    'employees' => '员工',
    'employee_management' => '员工管理',
    'staff_directory' => '员工名录',
    'payroll' => '工资单',
    'payslips' => '工资条',
    'generate_payslip' => '生成工资条',
    'salary' => '薪水',
    'allowances' => '津贴',
    'deductions' => '扣款',
    'net_salary' => '净薪',
    'gross_salary' => '毛薪',
    'contracts' => '合同',
    'employment_contract' => '劳动合同',
    'contract_start_date' => '合同开始日期',
    'contract_end_date' => '合同结束日期',
    'contract_type' => '合同类型',
    'permanent' => '正式',
    'temporary' => '临时',
    'contract' => '合同',
    'probation' => '试用期',

    // Leave Management
    'leave_management' => '请假管理',
    'leave_requests' => '请假申请',
    'leave_request' => '请假申请',
    'apply_for_leave' => '申请请假',
    'leave_type' => '请假类型',
    'sick_leave' => '病假',
    'annual_leave' => '年假',
    'casual_leave' => '事假',
    'maternity_leave' => '产假',
    'paternity_leave' => '陪产假',
    'unpaid_leave' => '无薪假',
    'leave_balance' => '请假余额',
    'leave_duration' => '请假时长',
    'leave_reason' => '请假原因',
    'leave_status' => '请假状态',
    'approve_leave' => '批准请假',
    'reject_leave' => '拒绝请假',

    // Self Account
    'self_account' => '个人账户',
    'my_profile' => '我的资料',
    'my_account' => '我的账户',
    'personal_information' => '个人信息',
    'change_password' => '修改密码',
    'my_payslips' => '我的工资条',
    'my_leaves' => '我的请假',
    'my_contracts' => '我的合同',

    // SMS Manager
    'sms_manager' => '短信管理',
    'send_sms' => '发送短信',
    'sms_history' => '短信历史',
    'bulk_sms' => '群发短信',
    'birthday_wishes' => '生日祝福',
    'appointment_reminders' => '预约提醒',
    'payment_reminders' => '付款提醒',
    'sms_templates' => '短信模板',
    'create_template' => '创建模板',
    'template_name' => '模板名称',
    'template_content' => '模板内容',
    'recipients' => '接收者',
    'select_recipients' => '选择接收者',
    'all_patients' => '所有患者',
    'active_patients' => '活跃患者',
    'custom_selection' => '自定义选择',
    'message' => '消息',
    'message_text' => '消息文本',
    'send_now' => '立即发送',
    'schedule_send' => '定时发送',
    'send_date' => '发送日期',
    'send_time' => '发送时间',

    // Notifications
    'notifications' => '通知',
    'notification' => '通知',
    'mark_as_read' => '标记为已读',
    'mark_all_as_read' => '全部标记为已读',
    'unread_notifications' => '未读通知',
    'no_notifications' => '没有通知',

    // Common Module Terms
    'today_summary' => '今日摘要',
    'quick_actions' => '快速操作',
    'recent_activity' => '最近活动',
    'pending_tasks' => '待处理任务',
    'alerts' => '提醒',
    'reminders' => '提醒事项',

];