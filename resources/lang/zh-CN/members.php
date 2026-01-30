<?php

return [
    // Page titles
    'page_title' => '会员管理',
    'all_members' => '所有会员',
    'member_details' => '会员详情',
    'member_levels' => '会员等级',

    // Actions
    'register_member' => '注册会员',
    'edit_member' => '编辑会员',
    'manage_levels' => '管理等级',
    'add_level' => '添加等级',
    'edit_level' => '编辑等级',
    'back_to_members' => '返回会员列表',
    'deposit' => '充值',
    'deposit_funds' => '会员充值',
    'confirm_deposit' => '确认充值',

    // Fields
    'member_no' => '会员编号',
    'patient' => '患者',
    'patient_name' => '患者姓名',
    'level' => '等级',
    'level_name' => '等级名称',
    'level_code' => '等级代码',
    'color' => '颜色',
    'balance' => '余额',
    'points' => '积分',
    'total_consumption' => '累计消费',
    'member_since' => '入会日期',
    'expiry_date' => '到期日期',
    'status' => '状态',
    'initial_balance' => '初始余额',
    'current_balance' => '当前余额',
    'deposit_amount' => '充值金额',
    'payment_method' => '支付方式',
    'description' => '说明',
    'discount' => '折扣',
    'discount_rate' => '支付比例',
    'discount_rate_hint' => '100% = 无折扣，90% = 9折',
    'min_consumption' => '最低消费',
    'points_rate' => '积分比例',
    'points_rate_hint' => '每消费1元获得的积分',
    'sort_order' => '排序',
    'is_active' => '是否启用',
    'benefits' => '会员权益',
    'member_info' => '会员信息',
    'transaction_history' => '交易记录',
    'transaction_no' => '交易编号',
    'transaction_type' => '类型',
    'amount' => '金额',
    'balance_after' => '交易后余额',
    'date' => '日期',

    // Status values
    'status_active' => '有效',
    'status_expired' => '已过期',
    'status_inactive' => '未激活',
    'all_statuses' => '所有状态',
    'all_levels' => '所有等级',

    // Transaction types
    'type_deposit' => '充值',
    'type_consumption' => '消费',
    'type_refund' => '退款',
    'type_adjustment' => '调整',

    // Payment methods
    'payment_cash' => '现金',
    'payment_card' => '银行卡',
    'payment_bank' => '银行转账',
    'payment_mobile' => '移动支付',

    // Messages
    'member_registered_successfully' => '会员注册成功。',
    'member_updated_successfully' => '会员信息更新成功。',
    'deposit_successful' => '充值成功。',
    'level_created_successfully' => '会员等级创建成功。',
    'level_updated_successfully' => '会员等级更新成功。',
    'level_deleted_successfully' => '会员等级删除成功。',
    'already_member' => '该患者已是会员。',
    'not_active_member' => '该会员状态不是有效。',
    'level_has_members' => '无法删除等级。有 :count 位会员使用此等级。',
    'initial_deposit' => '初始充值',
    'balance_deposit' => '余额充值',
    'no_discount' => '无折扣',

    // Empty state
    'no_members_found' => '暂无会员记录',
    'no_levels_found' => '暂无会员等级',

    // Confirmations
    'confirm_delete_level' => '确定要删除此等级吗？',
];
