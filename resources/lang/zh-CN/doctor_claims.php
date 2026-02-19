<?php

return [

    /*
     * Doctor Claims Language Lines
     * |--------------------------------------------------------------------------
     * | The following language lines are used for Doctor Claims management
     * |-------------------------------------------------------------------------
     */
    'title' => '薪资管理 /理赔',
    'doctor_claim_approval_form' => '医生理赔审批表',
    'doctor_claims_payment' => '医生理赔支付',
    'claim_payment_details' => '理赔/支付详情',
    'view_claims' => '查看理赔',
    'claims_form' => '理赔表单',

    // 表单字段
    'claim_amount' => '理赔金额',
    'insurance_amount' => '保险金额',
    'cash_amount' => '现金金额',
    'payment_date' => '支付日期',
    'amount' => '金额',
    'enter_claim_amount' => '输入金额',
    'enter_payment_date' => '年-月-日',
    'enter_insurance_amount' => '输入保险金额',
    'enter_cash_amount' => '输入现金金额',

    // 表格列
    'date' => '日期',
    'patient' => '患者',
    'doctor' => '医生',
    'treatment_amount' => '治疗金额',
    'insurance_claim' => '保险理赔',
    'cash_claim' => '现金理赔',
    'total_claim_amount' => '总理赔金额',
    'payment_balance' => '支付余额',

    // 按钮
    'approve_claim' => '批准理赔',
    'edit_claim' => '编辑理赔',
    'view_payment' => '查看支付',
    'make_payment' => '进行支付',
    'create_claim' => '创建理赔',

    // 状态
    'claim_already_generated' => '理赔已生成',

    // 确认对话框
    'delete_confirm_message' => '您将无法恢复此理赔！',
    'delete_payment_confirm_message' => '您将无法恢复此理赔支付！',
    // 成功消息
    'claim_submitted_successfully' => '理赔提交成功',
    'claim_updated_successfully' => '理赔更新成功',
    'claim_deleted_successfully' => '理赔删除成功',
    'claim_approved_successfully' => '医生理赔批准成功',
    'payment_saved_successfully' => '支付保存成功',
    'payment_updated_successfully' => '支付更新成功',
    'payment_deleted_successfully' => '支付删除成功',

    // 错误消息
    'no_claim_rate_in_system' => '抱歉，系统中没有您的理赔费率，请联系系统管理员',
    'amounts_not_matching' => '保险和现金总金额与治疗金额不匹配',

    'payments' => [

        'title' => '理赔/支付详情',
        'doctor_claims_payment' => '医生理赔支付',
        'view_claims' => '查看理赔',

        // 表单字段
        'enter_payment_date' => '年-月-日',
        'enter_amount_here' => '在此输入金额',

        // 表格列
        'hash' => '#',
        'payment_date' => '支付日期',
        'amount' => '金额',

        // 消息
        'payment_added_successfully' => '理赔支付已成功记录',
        'payment_updated_successfully' => '理赔支付已成功更新',
        'payment_deleted_successfully' => '理赔支付已成功删除',

        // 确认对话框
        'delete_payment_confirm' => '您将无法恢复此理赔支付！',
    ]
];