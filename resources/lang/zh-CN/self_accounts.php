<?php

return [

    /**
     * Self Accounts Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于储值账户管理。
     * 您可以根据应用程序的要求自由修改这些语言行。
     */

    // 页面标题
    'self_accounts' => '储值账户',
    'self_account' => '储值账户',
    'accounting_manager_self_accounts' => '会计管理/ 储值账户',
    'account_management' => '账户管理',
    'account_list' => '账户列表',
    'account_details' => '账户详情',
    'add_account' => '添加账户',
    'new_account' => '新建账户',
    'create_account' => '创建账户',
    'edit_account' => '编辑账户',
    'view_account' => '查看账户',
    'view_self_accounts' => '查看储值账户',
    'my_account' => '我的账户',
    'self_account_form' => '储值账户表单',
    'self_account_deposits' => '储值账户存款',
    'self_account_bill_payments' => '储值账户账单支付',

    // 表单标签
    'account_name' => '账户名称',
    'account_number' => '账户号码',
    'account_type' => '账户类型',
    'account_holder' => '账户持有人',
    'phone_no' => '电话号码',
    'phone_optional' => '电话号码：（可选）',
    'email_optional' => '电子邮箱（可选）',
    'address_optional' => '地址（可选）',
    'patient_name' => '患者姓名',
    'patient_id' => '患者编号',
    'account_balance' => '账户余额',
    'current_balance' => '当前余额',
    'available_balance' => '可用余额',
    'opening_balance' => '期初余额',
    'closing_balance' => '期末余额',
    'credit_limit' => '信用额度',
    'invoice_no' => '发票号',
    'patient' => '患者',
    'added_by' => '录入人',

    // 账户类型
    'prepaid' => '预付',
    'credit' => '信用',
    'deposit' => '存款',
    'savings' => '储蓄',
    'package' => '套餐账户',

    // 交易类型
    'deposit' => '存款',
    'withdrawal' => '提款',
    'payment' => '付款',
    'refund' => '退款',
    'transfer' => '转账',
    'adjustment' => '调整',
    'charge' => '收费',
    'credit_note' => '贷方凭证',
    'debit_note' => '借方凭证',

    // 状态
    'status' => '状态',
    'active' => '活跃',
    'inactive' => '不活跃',
    'suspended' => '已暂停',
    'closed' => '已关闭',
    'frozen' => '已冻结',

    // 表格表头
    'id' => '编号',
    'account_no' => '账户号',
    'holder_name' => '持有人姓名',
    'balance' => '余额',
    'created_date' => '创建日期',
    'last_transaction' => '最后交易',
    'actions' => '操作',

    // 交易
    'transactions' => '交易',
    'transaction_history' => '交易历史',
    'view_transactions' => '查看交易',
    'add_transaction' => '添加交易',
    'transaction_date' => '交易日期',
    'transaction_type' => '交易类型',
    'transaction_amount' => '交易金额',
    'transaction_reference' => '交易参考号',
    'description' => '描述',
    'reference_number' => '参考号',
    'remarks' => '备注',

    // 存款
    'make_deposit' => '进行存款',
    'deposit_amount' => '存款金额',
    'deposit_date' => '存款日期',
    'deposit_method' => '存款方式',
    'add_deposit' => '添加存款',
    'record_deposit' => '记录存款',

    // 提款
    'make_withdrawal' => '进行提款',
    'withdrawal_amount' => '提款金额',
    'withdrawal_date' => '提款日期',
    'record_withdrawal' => '记录提款',

    // 付款
    'make_payment' => '进行付款',
    'payment_amount' => '付款金额',
    'payment_date' => '付款日期',
    'payment_method' => '付款方式',
    'pay_from_account' => '从账户支付',
    'record_payment' => '记录付款',

    // 付款方式
    'cash' => '现金',
    'bank_transfer' => '银行转账',
    'cheque' => '支票',
    'credit_card' => '信用卡',
    'debit_card' => '借记卡',
    'mobile_money' => '移动支付',

    // 操作
    'view_details' => '查看详情',
    'view_statement' => '查看对账单',
    'download_statement' => '下载对账单',
    'print_statement' => '打印对账单',
    'close_account' => '关闭账户',
    'suspend_account' => '暂停账户',
    'activate_account' => '激活账户',
    'recharge_account' => '账户充值',

    // 占位符
    'enter_account_name' => '输入账户名称',
    'enter_amount' => '输入金额',
    'enter_description' => '输入描述',
    'enter_reference' => '输入参考号',
    'select_patient' => '选择患者',
    'select_account_type' => '选择账户类型',
    'choose_account' => '选择账户...',

    // 消息
    'account_created_successfully' => '账户创建成功',
    'account_updated_successfully' => '账户更新成功',
    'account_deleted_successfully' => '账户删除成功',
    'account_closed_successfully' => '账户关闭成功',
    'account_suspended_successfully' => '账户暂停成功',
    'account_activated_successfully' => '账户激活成功',
    'deposit_recorded_successfully' => '存款记录成功',
    'withdrawal_recorded_successfully' => '提款记录成功',
    'payment_recorded_successfully' => '付款记录成功',
    'insufficient_balance' => '余额不足',
    'confirm_close_account' => '您确定要关闭此账户吗？',
    'confirm_delete_account' => '您将无法恢复此账户！',
    'account_not_found' => '未找到账户',
    'error_creating_account' => '创建账户时出错',
    'error_processing_transaction' => '处理交易时出错',
    'no_accounts_found' => '未找到账户',
    'no_transactions_found' => '未找到交易',

    // 对账单
    'account_statement' => '账户对账单',
    'statement_period' => '对账期间',
    'opening_date' => '开始日期',
    'from_date' => '起始日期',
    'to_date' => '截止日期',
    'total_deposits' => '总存款',
    'total_withdrawals' => '总提款',
    'total_payments' => '总付款',
    'net_balance' => '净余额',

    // 搜索和筛选
    'search_accounts' => '搜索账户',
    'filter_accounts' => '筛选账户',
    'filter_by_status' => '按状态筛选',
    'filter_by_type' => '按类型筛选',
    'show_all' => '显示全部',

    // 统计信息
    'total_accounts' => '总账户数',
    'active_accounts' => '活跃账户',
    'total_balance' => '总余额',
    'total_deposits_today' => '今日总存款',

];
