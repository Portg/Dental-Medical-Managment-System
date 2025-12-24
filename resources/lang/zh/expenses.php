<?php

return [
    // 页面标题
    'title' => '费用管理 / 费用',
    'categories' => '费用类别',
    'suppliers' => '费用管理 / 供应商',

    // 表头
    'table' => [
        'purchase_date' => '采购日期',
        'supplier' => '供应商',
        'total_amount' => '总金额',
        'paid_amount' => '已付金额',
        'outstanding' => '未付款',
        'category' => '类别',
        'item' => '项目',
        'description' => '描述',
        'quantity' => '数量',
        'unit_price' => '单价',
    ],

    // 按钮
    'buttons' => [
        'filter_expenses' => '筛选费用',
        'save_purchase' => '保存采购',
    ],

    // 表单
    'form' => [
        'enter_item' => '请输入项目',
        'enter_description' => '请输入描述（可选）',
        'choose_category' => '请选择费用类别',
        'enter_quantity' => '请输入数量',
        'enter_unit_price' => '请输入单价',
        'total_amount' => '总金额',
        'supplier_name' => '供应商名称',
        'category_name' => '类别名称',
    ],

    // 仪表板
    'dashboard' => [
        'todays_expenses' => '今日费用（金额）',
    ],

    // 提示
    'alerts' => [
        'delete_confirm' => '您将无法恢复此费用！',
        'delete_supplier' => '您将无法恢复此供应商！',
        'delete_category' => '您将无法恢复此类别！',
    ],
];
