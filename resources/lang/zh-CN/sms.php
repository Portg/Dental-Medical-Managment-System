<?php

return [

    /**
     * SMS Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于短信管理。
     * 您可以根据应用程序的要求自由修改这些语言行。
     */

    // 页面标题
    'sms' => '短信',
    'sms_management' => '短信管理',
    'send_sms' => '发送短信',
    'sms_history' => '短信历史',
    'sms_logs' => '短信日志',
    'sms_templates' => '短信模板',
    'sms_settings' => '短信设置',
    'bulk_sms' => '批量短信',
    'compose_sms' => '编写短信',

    // 表单标签
    'recipient' => '接收人',
    'recipients' => '接收人',
    'phone_number' => '电话号码',
    'message' => '消息',
    'message_text' => '消息文本',
    'sender_id' => '发送者ID',
    'sender_name' => '发送者名称',
    'template' => '模板',
    'template_name' => '模板名称',
    'select_template' => '选择模板',
    'use_template' => '使用模板',

    // 短信类型
    'appointment_reminder' => '预约提醒',
    'payment_reminder' => '付款提醒',
    'birthday_wishes' => '生日祝福',
    'follow_up' => '复诊',
    'notification' => '通知',
    'promotional' => '促销',
    'custom' => '自定义',

    // 接收人选择
    'select_recipients' => '选择接收人',
    'all_patients' => '所有患者',
    'patients_with_appointments' => '有预约的患者',
    'patients_with_outstanding_balance' => '有未付余额的患者',
    'birthday_today' => '今日生日',
    'custom_selection' => '自定义选择',
    'select_patients' => '选择患者',
    'add_recipient' => '添加接收人',
    'remove_recipient' => '移除接收人',

    // 消息详情
    'characters' => '字符',
    'character_count' => '字符数',
    'message_parts' => '消息条数',
    'estimated_cost' => '预估费用',
    'total_recipients' => '总接收人数',
    'message_length' => '消息长度',

    // 状态
    'status' => '状态',
    'sent' => '已发送',
    'pending' => '待处理',
    'failed' => '失败',
    'delivered' => '已送达',
    'undelivered' => '未送达',
    'queued' => '排队中',

    // 表格表头
    'id' => '编号',
    'date' => '日期',
    'time' => '时间',
    'recipient_name' => '接收人姓名',
    'recipient_number' => '接收人号码',
    'message_preview' => '消息预览',
    'sent_by' => '发送人',
    'actions' => '操作',

    // 操作
    'send' => '发送',
    'send_now' => '立即发送',
    'schedule' => '定时',
    'schedule_sms' => '定时短信',
    'cancel' => '取消',
    'delete' => '删除',
    'view_details' => '查看详情',
    'resend' => '重新发送',
    'copy_message' => '复制消息',

    // 定时
    'schedule_date' => '定时日期',
    'schedule_time' => '定时时间',
    'send_at' => '发送时间',
    'scheduled_for' => '定时于',

    // 模板
    'create_template' => '创建模板',
    'edit_template' => '编辑模板',
    'delete_template' => '删除模板',
    'template_variables' => '模板变量',
    'available_variables' => '可用变量',
    'patient_name' => '患者姓名',
    'appointment_date' => '预约日期',
    'appointment_time' => '预约时间',
    'doctor_name' => '医生姓名',
    'clinic_name' => '诊所名称',
    'amount_due' => '应付金额',

    // 占位符
    'enter_message' => '输入消息',
    'enter_phone_number' => '输入电话号码',
    'enter_template_name' => '输入模板名称',
    'type_your_message' => '在此输入您的消息...',
    'search_patients' => '搜索患者',

    // 消息
    'appointment_scheduled' => '您好，:name 您在 :company 的预约已安排在 :date :time',
    'sms_sent_successfully' => '短信发送成功',
    'sms_scheduled_successfully' => '短信定时成功',
    'sms_failed' => '短信发送失败',
    'template_created_successfully' => '模板创建成功',
    'template_updated_successfully' => '模板更新成功',
    'template_deleted_successfully' => '模板删除成功',
    'confirm_send_sms' => '您确定要发送此短信吗？',
    'confirm_delete_template' => '您确定要删除此模板吗？',
    'no_recipients_selected' => '未选择接收人',
    'invalid_phone_number' => '无效的电话号码',
    'message_too_long' => '消息太长',
    'message_empty' => '消息不能为空',
    'insufficient_balance' => '短信余额不足',
    'no_sms_found' => '未找到短信记录',

    // 余额和积分
    'sms_balance' => '短信余额',
    'credits' => '积分',
    'credits_remaining' => '剩余积分',
    'buy_credits' => '购买积分',
    'topup' => '充值',
    'cost_per_sms' => '每条短信费用',

    // 设置
    'sms_gateway' => '短信网关',
    'api_key' => 'API密钥',
    'api_secret' => 'API密码',
    'sender_id_default' => '默认发送者ID',
    'enable_sms' => '启用短信',
    'disable_sms' => '禁用短信',
    'test_connection' => '测试连接',

    // 报表和统计
    'sms_report' => '短信报表',
    'total_sent' => '总发送数',
    'total_delivered' => '总送达数',
    'total_failed' => '总失败数',
    'delivery_rate' => '送达率',
    'sms_sent_today' => '今日发送',
    'sms_sent_this_month' => '本月发送',

    // 筛选
    'filter_by_status' => '按状态筛选',
    'filter_by_date' => '按日期筛选',
    'start_date' => '开始日期',
    'end_date' => '结束日期',
    'search' => '搜索',

    // outbox_sms/index页面附加内容
    'sms_manager_outbox' => '短信管理 / 发件箱',
    'download_excel_report' => '下载Excel报表',
    'period' => '时间段',
    'all' => '全部',
    'today' => '今天',
    'yesterday' => '昨天',
    'this_week' => '本周',
    'last_week' => '上周',
    'this_month' => '本月',
    'last_month' => '上月',
    'filter_sms' => '筛选短信',
    'clear' => '清除',
    'sent_date' => '发送日期',
    'phone_no' => '电话号码',
    'message_type' => '消息类型',
    'message_price' => '消息价格 (UGX)',
    'message_status' => '消息状态',
    'loading' => '加载中',
    'alert' => '提示！',

    // 短信交易
    'sms_transactions' => '短信交易',
    'sms_credit_loading' => '短信积分充值',
    'load_sms_credit' => '充值短信积分',
    'credit_amount' => '积分金额',
    'enter_credit_amount' => '输入积分金额',
    'transaction_reference' => '交易参考号',
    'enter_reference' => '输入参考号',
    'transaction_date' => '交易日期',
    'loaded_by' => '充值人',
    'save_transaction' => '保存交易',
    'close' => '关闭',
    'processing' => '处理中...',
    'save_changes' => '保存更改',

];
