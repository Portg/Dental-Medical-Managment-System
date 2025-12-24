<?php

return [
    // 页面标题
    'title' => '患者管理 / 患者',
    'patient_history' => '患者历史',

    // 表头
    'table' => [
        'surname' => '姓',
        'other_name' => '名',
        'gender' => '性别',
        'dob' => '出生日期',
        'email_address' => '电子邮件地址',
        'contacts' => '联系方式',
        'next_of_kin' => '紧急联系人',
        'medical_aid' => '医疗援助',
    ],

    // 表单
    'form' => [
        'title' => '患者表单',
        'surname' => '姓',
        'other_name' => '名',
        'gender' => '性别',
        'dob' => '出生日期',
        'email' => '电子邮件',
        'phone_no' => '电话号码',
        'alt_phone' => '备用电话号码',
        'address' => '地址',
        'national_id' => '身份证号',
        'profession' => '职业 / 工作单位',
        'next_of_kin' => '紧急联系人',
        'next_of_kin_phone' => '紧急联系人（电话号码）',
        'next_of_kin_address' => '紧急联系人地址',
        'has_insurance' => '患者是否有医疗保险',
        'insurance_company' => '医疗保险公司',
    ],

    // 占位符
    'placeholders' => [
        'enter_surname' => '请输入姓',
        'enter_other_name' => '请输入名',
        'enter_email' => '请输入电子邮件',
        'enter_phone' => '电话号码',
        'enter_alt_phone' => '备用号码',
        'enter_address' => '请输入地址',
        'enter_national_id' => '身份证号',
        'enter_profession' => '请输入职业',
        'enter_next_of_kin' => '请输入紧急联系人',
        'enter_kin_phone' => '请输入电话',
        'enter_kin_address' => '请输入地址',
    ],

    // 筛选
    'filters' => [
        'insurance_company' => '保险公司',
        'filter_patients' => '筛选患者',
    ],

    // 提示
    'alerts' => [
        'delete_confirm' => '您将无法恢复此患者信息！',
    ],
];
