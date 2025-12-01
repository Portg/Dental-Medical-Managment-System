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
    'record_created_successfully' => '记录创建成功！',
    'record_updated_successfully' => '记录更新成功！',
    'record_deleted_successfully' => '记录删除成功！',

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

];
