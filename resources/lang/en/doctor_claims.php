<?php

return [

    /*
     * Doctor Claims Language Lines
     * |--------------------------------------------------------------------------
     * | The following language lines are used for Doctor Claims management
     * |-------------------------------------------------------------------------
     */
    'title' => 'Payroll Management /Claims',
    'doctor_claim_approval_form' => 'Doctor Claim Approval Form',
    'doctor_claims_payment' => 'Doctor Claims Payment',
    'claim_payment_details' => 'Claim /Payment details',
    'view_claims' => 'View Claims',
    'claims_form' => 'Claims Form',

    // 表单字段
    'claim_amount' => 'Claim Amount',
    'insurance_amount' => 'Insurance Amount',
    'cash_amount' => 'Cash Amount',
    'payment_date' => 'Payment Date',
    'amount' => 'Amount',
    'enter_claim_amount' => 'Enter Amount',
    'enter_payment_date' => 'yyyy-mm-dd',
    'enter_insurance_amount' => 'Enter Insurance Amount',
    'enter_cash_amount' => 'Enter Cash Amount',

    // 表格列
    'date' => 'Date',
    'patient' => 'Patient',
    'doctor' => 'Doctor',
    'treatment_amount' => 'Treatment Amount',
    'insurance_claim' => 'Insurance Claim',
    'cash_claim' => 'Cash Claim',
    'total_claim_amount' => 'Total Claim Amount',
    'payment_balance' => 'Payment Balance',

    // 按钮
    'approve_claim' => 'Approve Claim',
    'edit_claim' => 'Edit Claim',
    'view_payment' => 'View Payment',
    'make_payment' => 'Make Payment',
    'create_claim' => 'Create Claim',

    // 状态
    'claim_already_generated' => 'Claim already generated',

    // 确认对话框
    'delete_confirm_message' => 'Your will not be able to recover this Claim!',
    'delete_payment_confirm_message' => 'Your will not be able to recover this Claim payment!',
    // 成功消息
    'claim_submitted_successfully' => 'Claim has been submitted successfully',
    'claim_updated_successfully' => 'Claim has been updated successfully',
    'claim_deleted_successfully' => 'Claim has been deleted successfully',
    'claim_approved_successfully' => 'Doctor claim has been approved successfully',
    'payment_saved_successfully' => 'Payment saved successfully',
    'payment_updated_successfully' => 'Payment updated successfully',
    'payment_deleted_successfully' => 'Payment deleted successfully',

    // 错误消息
    'no_claim_rate_in_system' => 'Sorry you dont have claim rate in the system, please contact the system admin',
    'amounts_not_matching' => 'Total insurance & Cash Amounts are not matching with the treatment amount',

    'payments' => [

        'title' => 'Claim /Payment details',
        'doctor_claims_payment' => 'Doctor Claims Payment',
        'view_claims' => 'View Claims',

        // 表单字段
        'enter_payment_date' => 'yyyy-mm-dd',
        'enter_amount_here' => 'Enter Amount Here',

        // 表格列
        'hash' => '#',
        'payment_date' => 'Payment Date',
        'amount' => 'Amount',

        // 消息
        'payment_added_successfully' => 'Claim payment has been captured successfully',
        'payment_updated_successfully' => 'Claim payment has been updated successfully',
        'payment_deleted_successfully' => 'Claim payment has been deleted successfully',

        // 确认对话框
        'delete_payment_confirm' => 'Your will not be able to recover this Claim payment!',
    ]
];