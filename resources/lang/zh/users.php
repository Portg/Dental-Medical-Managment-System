<?php

return [
    // 页面标题
    'title' => '用户管理 / 用户',
    'roles' => '用户管理 / 角色',

    // 表头
    'table' => [
        'surname' => '姓',
        'othername' => '名',
        'email' => '电子邮件',
        'phone_number' => '电话号码',
        'role' => '角色',
        'branch' => '分支',
        'is_doctor' => '是否医生',
    ],

    // 表单
    'form' => [
        'title' => '系统用户',
        'surname' => '姓',
        'other_name' => '名',
        'email' => '电子邮件',
        'phone_no' => '电话号码',
        'alt_phone' => '备用电话号码',
        'national_id' => '身份证号',
        'password' => '密码（首选）',
        'confirm_password' => '确认密码',
        'role' => '系统用户角色（必填）',
        'branch' => '分支',
        'is_doctor' => '是否医生',
        'is_doctor_note' => '（如果用户是医生请指定）',
    ],

    // 占位符
    'placeholders' => [
        'enter_surname' => '请输入姓',
        'enter_other_name' => '请输入名',
        'email_address' => '电子邮件地址',
        'phone_number' => '电话号码',
        'alternative_no' => '备用号码',
        'id_no' => '身份证号',
    ],

    // 提示
    'alerts' => [
        'delete_confirm' => '您将无法恢复此用户！',
        'delete_role' => '您将无法恢复此角色！',
    ],
];
