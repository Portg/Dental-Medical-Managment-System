<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Controller Messages Language Lines
    |--------------------------------------------------------------------------
    |
    | Messages returned by controllers for various operations
    |
    */

    // Success Messages
    'operation_successful' => '操作成功！',
    'data_saved_successfully' => '数据保存成功！',
    'data_updated_successfully' => '数据更新成功！',
    'data_deleted_successfully' => '数据删除成功！',
    'data_deleted' => '数据已删除',
    'record_created_successfully' => '记录创建成功！',
    'record_updated_successfully' => '记录更新成功！',
    'record_deleted_successfully' => '记录删除成功！',

    // Message Messages
    'message_added_successfully' => '消息添加成功',
    'message_updated_successfully' => '消息更新成功',
    'message_deleted_successfully' => '消息删除成功',

    // Patient Messages
    'patient_added_successfully' => '患者添加成功！',
    'patient_updated_successfully' => '患者更新成功！',
    'patient_deleted_successfully' => '患者删除成功！',

    // Appointment Messages
    'appointment_created_successfully' => '预约创建成功！',
    'appointment_updated_successfully' => '预约更新成功！',
    'appointment_deleted_successfully' => '预约删除成功！',
    'appointment_confirmed_successfully' => '预约确认成功！',
    'appointment_cancelled_successfully' => '预约取消成功！',
    'appointment_status_updated' => '预约已保存为 :status',

    // Invoice Messages
    'invoice_created_successfully' => '发票创建成功！',
    'invoice_updated_successfully' => '发票更新成功！',
    'invoice_deleted_successfully' => '发票删除成功！',
    'invoice_sent_successfully' => '发票发送成功！',

    // Payment Messages
    'payment_recorded_successfully' => '付款记录成功！',
    'payment_updated_successfully' => '付款更新成功！',
    'payment_deleted_successfully' => '付款删除成功！',
    'payment_received_successfully' => '收款成功！',

    // Prescription Messages
    'prescription_created_successfully' => '处方创建成功！',
    'prescription_updated_successfully' => '处方更新成功！',
    'prescription_deleted_successfully' => '处方删除成功！',

    // User Messages
    'user_created_successfully' => '用户创建成功！',
    'user_updated_successfully' => '用户更新成功！',
    'user_deleted_successfully' => '用户删除成功！',
    'password_changed_successfully' => '密码修改成功！',
    'profile_updated_successfully' => '资料更新成功！',

    // SMS Messages
    'sms_sent_successfully' => '短信发送成功！',
    'sms_scheduled_successfully' => '短信定时发送设置成功！',

    // Email Messages
    'email_sent_successfully' => '邮件发送成功！',

    // Error Messages
    'operation_failed' => '操作失败！',
    'error_occurred' => '发生错误，请重试。',
    'error_occurred_later' => '发生错误，请稍后重试',
    'oops_error_occurred' => '哎呀！发生错误，请重试。',
    'something_went_wrong' => '出错了，请重试。',
    'could_not_process_request' => '无法处理请求。',
    'invalid_request' => '无效的请求。',
    'unauthorized_access' => '未授权访问。',
    'permission_denied' => '没有权限执行此操作。',
    'access_denied' => '访问被拒绝。',
    'not_found' => '未找到请求的资源。',
    'record_not_found' => '未找到记录。',
    'data_not_found' => '未找到数据。',

    // Validation Messages
    'validation_error' => '验证错误。',
    'please_check_input' => '请检查输入。',
    'required_fields_missing' => '必填字段缺失。',
    'invalid_input' => '输入无效。',
    'invalid_data' => '数据无效。',
    'duplicate_entry' => '重复条目。',
    'already_exists' => '已存在。',

    // Specific Error Messages
    'patient_not_found' => '未找到患者。',
    'appointment_not_found' => '未找到预约。',
    'invoice_not_found' => '未找到发票。',
    'user_not_found' => '未找到用户。',
    'record_has_dependencies' => '此记录有关联数据，无法删除。',
    'cannot_delete_record' => '无法删除记录。',

    // Database Errors
    'database_error' => '数据库错误。',
    'connection_error' => '连接错误。',
    'query_failed' => '查询失败。',

    // File Upload Messages
    'file_uploaded_successfully' => '文件上传成功！',
    'file_upload_failed' => '文件上传失败。',
    'invalid_file_type' => '无效的文件类型。',
    'file_too_large' => '文件太大。',
    'file_not_found' => '未找到文件。',

    // Authentication Messages
    'login_successful' => '登录成功！',
    'logout_successful' => '退出成功！',
    'invalid_credentials' => '无效的凭据。',
    'account_disabled' => '账户已禁用。',
    'account_locked' => '账户已锁定。',
    'session_expired' => '会话已过期。',

    // Confirmation Messages
    'are_you_sure' => '您确定吗？',
    'confirm_delete' => '您确定要删除吗？',
    'confirm_action' => '您确定要执行此操作吗？',
    'action_cannot_be_undone' => '此操作无法撤销！',
    'please_confirm' => '请确认。',

    // Status Messages
    'status_updated_successfully' => '状态更新成功！',
    'activated_successfully' => '激活成功！',
    'deactivated_successfully' => '停用成功！',
    'enabled_successfully' => '启用成功！',
    'disabled_successfully' => '禁用成功！',

    // Notification Messages
    'notification_sent' => '通知已发送。',
    'reminder_sent' => '提醒已发送。',
    'alert_sent' => '提醒已发送。',

    // No Data Messages
    'no_data_available' => '没有可用数据。',
    'no_records_found' => '未找到记录。',
    'no_results_found' => '未找到结果。',
    'empty_list' => '列表为空。',

    // Process Messages
    'processing' => '处理中...',
    'please_wait' => '请稍候...',
    'loading' => '加载中...',
    'saving' => '保存中...',
    'updating' => '更新中...',
    'deleting' => '删除中...',
    'sending' => '发送中...',

    // Completion Messages
    'process_completed' => '处理完成。',
    'task_completed' => '任务完成。',
    'action_completed' => '操作完成。',

    // Warning Messages
    'warning' => '警告',
    'caution' => '注意',
    'please_note' => '请注意',
    'important' => '重要',

    // Info Messages
    'info' => '信息',
    'note' => '备注',
    'tip' => '提示',
    'help' => '帮助',

    // Specific Module Messages
    // Appointment Messages (additional)
    'appointment_rescheduled_successfully' => '患者预约已成功改期',
    'no_invoice_yet' => '暂无发票',
    'invoice_already_generated' => '发票已生成',

    // User Messages (additional)
    'user_registered_successfully' => '用户注册成功',

    // Accounting Messages
    'accounting_equation_added_successfully' => '会计等式添加成功',
    'accounting_equation_updated_successfully' => '会计等式更新成功',
    'accounting_equation_deleted_successfully' => '会计等式删除成功',

    // Salary Messages
    'salary_deduction_added_successfully' => '员工薪资扣除添加成功',
    'salary_deduction_updated_successfully' => '员工薪资扣除更新成功',
    'salary_deduction_deleted_successfully' => '员工薪资扣除删除成功',
    'salary_allowance_added_successfully' => '员工津贴添加成功',
    'salary_allowance_updated_successfully' => '员工津贴更新成功',
    'salary_allowance_deleted_successfully' => '员工津贴删除成功',

    // Leave Type Messages
    'leave_type_added_successfully' => '请假类型添加成功',
    'leave_type_updated_successfully' => '请假类型更新成功',
    'leave_type_deleted_successfully' => '请假类型删除成功',

    // Online Booking Messages
    'booking_approved_successfully' => '预约已成功批准',
    'booking_rejected_successfully' => '预约已成功拒绝',
    'booking_request_sent_successfully' => '您的预约请求已成功发送',

    // Chart of Account Messages
    'chart_account_category_added_successfully' => '会计科目类别添加成功',
    'chart_account_category_updated_successfully' => '会计科目类别更新成功',
    'chart_account_category_deleted_successfully' => '会计科目类别删除成功',

    // Chronic Disease Messages
    'chronic_disease_added_successfully' => '慢性病添加成功',
    'chronic_disease_updated_successfully' => '慢性病更新成功',
    'chronic_disease_deleted_successfully' => '慢性病删除成功',

    // Allergy Messages
    'allergy_added_successfully' => '过敏信息添加成功',
    'allergy_updated_successfully' => '过敏信息更新成功',
    'allergy_deleted_successfully' => '过敏信息删除成功',

    // Surgery Messages
    'surgery_added_successfully' => '手术记录添加成功',
    'surgery_updated_successfully' => '手术记录更新成功',
    'surgery_deleted_successfully' => '手术记录删除成功',

    // Branch Messages
    'branch_added_successfully' => '分店添加成功',
    'branch_updated_successfully' => '分店更新成功',
    'branch_deleted_successfully' => '分店删除成功',

    // Expense Messages
    'expense_added_successfully' => '支出添加成功',
    'expense_updated_successfully' => '支出更新成功',
    'expense_deleted_successfully' => '支出删除成功',
    'expense_category_added_successfully' => '支出类别添加成功',
    'expense_category_updated_successfully' => '支出类别更新成功',
    'expense_category_deleted_successfully' => '支出类别删除成功',
    'expense_item_added_successfully' => '支出项目添加成功',
    'expense_item_updated_successfully' => '支出项目更新成功',
    'expense_item_deleted_successfully' => '支出项目删除成功',

    // Quotation Messages
    'quotation_added_successfully' => '报价单添加成功',
    'quotation_updated_successfully' => '报价单更新成功',
    'quotation_deleted_successfully' => '报价单删除成功',
    'quotation_sent_successfully' => '报价单发送成功',

    // Permission/Role Messages
    'permission_added_successfully' => '权限添加成功',
    'permission_updated_successfully' => '权限更新成功',
    'permission_deleted_successfully' => '权限删除成功',
    'role_permission_added_successfully' => '角色权限添加成功',
    'role_permission_updated_successfully' => '角色权限更新成功',
    'role_permission_deleted_successfully' => '角色权限删除成功',

    // Confirmation Messages (additional)
    'confirm_save_changes' => '您确定要保存更改吗？',
    'confirm_delete_permission' => '您确定要删除此权限吗？',
    'confirm_delete_role_permission' => '您确定要删除此角色权限吗？',
    'confirm_delete_data' => '您确定要删除此数据吗？',
    'please_select_checkbox' => '请至少选择一个复选框',
    'cannot_recover_expense' => '您将无法恢复此支出！',

    // Medical Card Messages
    'medical_card_added_successfully' => '医疗卡添加成功',
    'medical_card_updated_successfully' => '医疗卡更新成功',
    'medical_card_deleted_successfully' => '医疗卡删除成功',

    // Treatment Messages
    'treatment_added_successfully' => '治疗记录添加成功',
    'treatment_updated_successfully' => '治疗记录更新成功',
    'treatment_deleted_successfully' => '治疗记录删除成功',

    // Supplier Messages
    'supplier_added_successfully' => '供应商添加成功',
    'supplier_updated_successfully' => '供应商更新成功',
    'supplier_deleted_successfully' => '供应商删除成功',

    // Role Messages
    'role_added_successfully' => '角色添加成功',
    'role_updated_successfully' => '角色更新成功',
    'role_deleted_successfully' => '角色删除成功',

    // Holiday Messages
    'holiday_added_successfully' => '假日添加成功',
    'holiday_updated_successfully' => '假日更新成功',
    'holiday_deleted_successfully' => '假日删除成功',

    // Insurance Messages
    'insurance_company_added_successfully' => '保险公司添加成功',
    'insurance_company_updated_successfully' => '保险公司更新成功',
    'insurance_company_deleted_successfully' => '保险公司删除成功',

    // Contract Messages
    'contract_added_successfully' => '合同添加成功',
    'contract_updated_successfully' => '合同更新成功',
    'contract_deleted_successfully' => '合同删除成功',

    // Leave Request Messages
    'leave_request_submitted_successfully' => '请假申请提交成功',
    'leave_request_approved_successfully' => '请假申请批准成功',
    'leave_request_rejected_successfully' => '请假申请拒绝成功',

    // Claim Messages
    'claim_added_successfully' => '理赔添加成功',
    'claim_updated_successfully' => '理赔更新成功',
    'claim_deleted_successfully' => '理赔删除成功',
    'claim_rate_added_successfully' => '理赔费率添加成功',
    'claim_rate_updated_successfully' => '理赔费率更新成功',
    'claim_rate_deleted_successfully' => '理赔费率删除成功',

    // Service Messages
    'service_added_successfully' => '服务添加成功',
    'service_updated_successfully' => '服务更新成功',
    'service_deleted_successfully' => '服务删除成功',

    // Self Account Messages
    'deposit_added_successfully' => '存款添加成功',
    'deposit_updated_successfully' => '存款更新成功',
    'deposit_deleted_successfully' => '存款删除成功',
    'self_account_added_successfully' => '储值账户添加成功',
    'self_account_updated_successfully' => '储值账户更新成功',
    'self_account_deleted_successfully' => '储值账户删除成功',

    // Salary Advance Messages
    'salary_advance_added_successfully' => '预支薪资添加成功',
    'salary_advance_updated_successfully' => '预支薪资更新成功',
    'salary_advance_deleted_successfully' => '预支薪资删除成功',

    // Payslip Messages
    'payslip_added_successfully' => '工资单添加成功',
    'payslip_updated_successfully' => '工资单更新成功',
    'payslip_deleted_successfully' => '工资单删除成功',

    // 通用记录消息（用于控制器）
    'record_updated' => '记录更新成功',
    'record_deleted' => '记录删除成功',
    'error_try_again' => '发生错误，请稍后重试',

    // Medical Template Messages
    'template_created_successfully' => '模板创建成功',
    'template_updated_successfully' => '模板更新成功',
    'template_deleted_successfully' => '模板删除成功',

    // Quick Phrase Messages
    'phrase_created_successfully' => '短语创建成功',
    'phrase_updated_successfully' => '短语更新成功',
    'phrase_deleted_successfully' => '短语删除成功',

    // Patient Tag Messages
    'tag_created_successfully' => '标签创建成功',
    'tag_updated_successfully' => '标签更新成功',
    'tag_deleted_successfully' => '标签删除成功',

    // Patient Source Messages
    'source_created_successfully' => '来源创建成功',
    'source_updated_successfully' => '来源更新成功',
    'source_deleted_successfully' => '来源删除成功',
    'source_in_use' => '无法删除此来源，因为它正在被患者使用',

];