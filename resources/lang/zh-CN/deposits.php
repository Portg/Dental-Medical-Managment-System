<?php

return [

    /**
     * Deposits Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于存款管理。
     * 您可以根据应用程序的要求自由修改这些语言行。
     */

    // 页面标题
    'deposits' => '存款',
    'deposit' => '存款',
    'deposit_management' => '存款管理',
    'deposit_list' => '存款列表',
    'deposit_details' => '存款详情',
    'add_deposit' => '添加存款',
    'new_deposit' => '新建存款',
    'create_deposit' => '创建存款',
    'record_deposit' => '记录存款',
    'edit_deposit' => '编辑存款',
    'view_deposit' => '查看存款',
    'deposit_history' => '存款历史',
    'deposit_form' => '自助账户存款表单',

    // 表单标签
    'deposit_date' => '存款日期',
    'deposit_amount' => '存款金额',
    'amount' => '金额',
    'depositor' => '存款人',
    'depositor_name' => '存款人姓名',
    'patient_name' => '患者姓名',
    'patient_id' => '患者编号',
    'account_number' => '账户号码',
    'reference_number' => '参考号',
    'transaction_id' => '交易编号',
    'deposit_method' => '存款方式',
    'payment_method' => '付款方式',
    'description' => '描述',
    'notes' => '备注',
    'remarks' => '备注',
    'purpose' => '用途',

    // 存款方式
    'cash' => '现金',
    'bank_transfer' => '银行转账',
    'cheque' => '支票',
    'credit_card' => '信用卡',
    'debit_card' => '借记卡',
    'mobile_money' => '移动支付',
    'online_payment' => '在线支付',
    'wire_transfer' => '电汇',

    // 存款类型
    'deposit_type' => '存款类型',
    'treatment_deposit' => '治疗押金',
    'advance_payment' => '预付款',
    'security_deposit' => '保证金',
    'refundable_deposit' => '可退还押金',
    'non_refundable_deposit' => '不可退还押金',
    'package_deposit' => '套餐押金',

    // 银行详情
    'bank_details' => '银行详情',
    'bank_name' => '银行名称',
    'account_name' => '账户名称',
    'cheque_number' => '支票号',
    'cheque_date' => '支票日期',
    'branch_name' => '分行名称',

    // 状态
    'status' => '状态',
    'pending' => '待处理',
    'confirmed' => '已确认',
    'cleared' => '已清算',
    'failed' => '失败',
    'cancelled' => '已取消',
    'refunded' => '已退款',
    'partially_refunded' => '部分退款',

    // 表格表头
    'id' => '编号',
    'date' => '日期',
    'patient' => '患者',
    'method' => '方式',
    'received_by' => '接收人',
    'actions' => '操作',

    // 操作
    'view_details' => '查看详情',
    'confirm_deposit' => '确认存款',
    'cancel_deposit' => '取消存款',
    'refund_deposit' => '退还存款',
    'print_receipt' => '打印收据',
    'download_receipt' => '下载收据',
    'email_receipt' => '邮件发送收据',
    'delete_deposit' => '删除存款',

    // 占位符
    'enter_amount' => '输入金额',
    'enter_patient_name' => '输入患者姓名',
    'enter_reference_number' => '输入参考号',
    'enter_description' => '输入描述',
    'select_patient' => '选择患者',
    'select_deposit_method' => '选择存款方式',
    'select_account' => '选择账户',
    'choose_patient' => '选择患者...',

    // 消息
    'deposit_recorded_successfully' => '存款记录成功',
    'deposit_updated_successfully' => '存款更新成功',
    'deposit_deleted_successfully' => '存款删除成功',
    'deposit_confirmed_successfully' => '存款确认成功',
    'deposit_cancelled_successfully' => '存款取消成功',
    'deposit_refunded_successfully' => '存款退还成功',
    'receipt_sent_successfully' => '收据发送成功',
    'confirm_delete_deposit' => '您确定要删除此存款吗？',
    'confirm_cancel_deposit' => '您确定要取消此存款吗？',
    'confirm_refund_deposit' => '您确定要退还此存款吗？',
    'deposit_not_found' => '未找到存款',
    'error_recording_deposit' => '记录存款时出错',
    'error_updating_deposit' => '更新存款时出错',
    'error_deleting_deposit' => '删除存款时出错',
    'no_deposits_found' => '未找到存款',
    'insufficient_balance_for_refund' => '余额不足，无法退款',

    // 搜索和筛选
    'search_deposits' => '搜索存款',
    'filter_deposits' => '筛选存款',
    'filter_by_patient' => '按患者筛选',
    'filter_by_status' => '按状态筛选',
    'filter_by_method' => '按方式筛选',
    'filter_by_date' => '按日期筛选',
    'start_date' => '开始日期',
    'end_date' => '结束日期',
    'show_all' => '显示全部',

    // 报表和统计
    'total_deposits' => '总存款数',
    'total_amount' => '总金额',
    'deposits_today' => '今日存款',
    'deposits_this_week' => '本周存款',
    'deposits_this_month' => '本月存款',
    'deposits_report' => '存款报表',
    'deposits_by_method' => '按方式分类存款',

    // 收据
    'deposit_receipt' => '存款收据',
    'receipt_number' => '收据号',
    'received_from' => '收款来自',
    'received_by' => '接收人',
    'payment_for' => '付款用途',
    'thank_you' => '谢谢',

    // 退款
    'refund' => '退款',
    'refund_amount' => '退款金额',
    'refund_date' => '退款日期',
    'refund_reason' => '退款原因',
    'partial_refund' => '部分退款',
    'full_refund' => '全额退款',
    'refund_method' => '退款方式',
    'refunded_amount' => '已退款金额',
    'remaining_balance' => '剩余余额',

    // 验证
    'amount_required' => '金额为必填项',
    'patient_required' => '患者为必填项',
    'method_required' => '存款方式为必填项',
    'invalid_amount' => '无效的金额',
    'amount_must_be_positive' => '金额必须为正数',
    'refund_amount_exceeds_deposit' => '退款金额超过存款金额',

    // 控制器消息
    'deposit_success' => '资金已成功存入自助账户',

];
