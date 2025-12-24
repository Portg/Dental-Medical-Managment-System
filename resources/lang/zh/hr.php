<?php

return [
    // 工资单
    'payroll' => [
        'title' => '工资管理 / 员工工资单',
        'employee' => '员工',
        'month' => '月份',
        'gross_commission' => '总额/佣金',
        'allowance' => '津贴',
        'deductions' => '扣除',
        'paid' => '已付',
        'outstanding' => '未付款',
        'include_allowances' => '包括津贴？',
        'include_deductions' => '包括扣除？',
    ],

    // 津贴
    'allowances' => [
        'house_rent' => '房租津贴',
        'medical' => '医疗津贴',
        'bonus' => '奖金',
        'dearness' => '物价津贴',
        'travelling' => '差旅津贴',
        'overtime' => '加班津贴',
    ],

    // 扣除
    'deductions' => [
        'loan' => '贷款',
        'tax' => '税',
    ],

    // 请假管理
    'leave' => [
        'title' => '请假管理 / 请假申请',
        'approval_title' => '请假管理 / 请假审批',
        'types_title' => '请假管理 / 请假类型',
        'request_date' => '申请日期',
        'leave_type' => '请假类型',
        'start_date' => '开始日期',
        'duration' => '时长',
        'delete_confirm' => '您将无法恢复此请假申请！',
    ],

    // 合同
    'contracts' => [
        'title' => '员工合同',
        'contract_no' => '合同编号',
        'start_date' => '开始日期',
        'end_date' => '结束日期',
        'salary' => '工资',
        'terms' => '条款',
    ],

    // 假期
    'holidays' => [
        'title' => '假期',
        'holiday_name' => '假期名称',
        'holiday_date' => '假期日期',
        'delete_confirm' => '您将无法恢复此假期！',
    ],
];
