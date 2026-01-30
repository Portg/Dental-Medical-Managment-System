<?php

return [
    // 模块
    'inventory_management' => '库存管理',
    'categories' => '物品分类',
    'items' => '物品目录',
    'stock_in' => '入库管理',
    'stock_out' => '出库管理',
    'stock_warnings' => '库存预警',
    'expiry_warnings' => '有效期预警',
    'service_consumables' => '项目耗材设置',

    // 分类类型
    'type_drug' => '药品',
    'type_consumable' => '耗材',
    'type_instrument' => '器械',
    'type_dental_material' => '义齿材料',
    'type_office' => '办公用品',

    // 分类字段
    'category' => '分类',
    'category_name' => '分类名称',
    'category_code' => '分类代码',
    'category_type' => '分类类型',
    'description' => '描述',
    'sort_order' => '排序',

    // 物品字段
    'item' => '物品',
    'item_code' => '物品编码',
    'item_name' => '物品名称',
    'specification' => '规格型号',
    'unit' => '单位',
    'brand' => '品牌/厂家',
    'reference_price' => '参考进价',
    'selling_price' => '销售价格',
    'track_expiry' => '有效期管理',
    'stock_warning_level' => '安全库存',
    'storage_location' => '存放位置',
    'current_stock' => '当前库存',
    'average_cost' => '平均成本',
    'notes' => '备注',

    // 库存状态
    'low_stock' => '库存不足',
    'in_stock' => '库存正常',
    'out_of_stock' => '缺货',
    'shortage' => '短缺数量',

    // 入库字段
    'stock_in_no' => '入库单号',
    'stock_in_date' => '入库日期',
    'supplier' => '供应商',
    'batch_no' => '批次号',
    'expiry_date' => '有效期',
    'production_date' => '生产日期',
    'unit_price' => '单价',
    'quantity' => '数量',
    'amount' => '金额',
    'total_amount' => '总金额',
    'confirm_stock_in' => '确认入库',

    // 出库字段
    'stock_out_no' => '出库单号',
    'stock_out_date' => '出库日期',
    'out_type' => '出库类型',
    'out_type_treatment' => '诊疗消耗',
    'out_type_department' => '科室领用',
    'out_type_damage' => '报损',
    'out_type_other' => '其他',
    'department' => '科室',
    'unit_cost' => '单位成本',
    'confirm_stock_out' => '确认出库',

    // 状态
    'status' => '状态',
    'status_draft' => '草稿',
    'status_confirmed' => '已确认',
    'status_cancelled' => '已取消',

    // 有效期预警
    'expired' => '已过期',
    'expiring_soon' => '即将过期',
    'near_expiry' => '临近过期',
    'days_to_expiry' => '距过期天数',
    'warning_days' => '预警天数',

    // 项目耗材
    'service' => '服务项目',
    'consumable' => '耗材',
    'required' => '必需',
    'optional' => '可选',
    'consumable_qty' => '耗材用量',

    // 消息
    'category_added_successfully' => '分类添加成功',
    'category_updated_successfully' => '分类更新成功',
    'category_deleted_successfully' => '分类删除成功',
    'category_has_items' => '无法删除含有物品的分类',

    'item_added_successfully' => '物品添加成功',
    'item_updated_successfully' => '物品更新成功',
    'item_deleted_successfully' => '物品删除成功',
    'item_has_movements' => '无法删除有出入库记录的物品',

    'stock_in_created_successfully' => '入库单创建成功',
    'stock_in_updated_successfully' => '入库单更新成功',
    'stock_in_deleted_successfully' => '入库单删除成功',
    'stock_in_confirmed' => '入库单已确认',
    'stock_in_cancelled' => '入库单已取消',

    'stock_out_created_successfully' => '出库单创建成功',
    'stock_out_updated_successfully' => '出库单更新成功',
    'stock_out_deleted_successfully' => '出库单删除成功',
    'stock_out_confirmed' => '出库单已确认',
    'stock_out_cancelled' => '出库单已取消',

    'consumable_added_successfully' => '项目耗材设置添加成功',
    'consumable_updated_successfully' => '项目耗材设置更新成功',
    'consumable_deleted_successfully' => '项目耗材设置删除成功',
    'consumable_already_exists' => '该耗材已配置在此服务项目中',

    // 验证消息
    'service_required' => '请选择服务项目',
    'item_required' => '请选择物品',
    'qty_required' => '请输入数量',
    'qty_min' => '数量必须大于0',
    'category_required' => '请选择分类',
    'category_name_required' => '请输入分类名称',
    'category_code_required' => '请输入分类代码',
    'category_code_unique' => '分类代码已存在',
    'category_type_required' => '请选择分类类型',
    'item_code_required' => '请输入物品编码',
    'item_code_unique' => '物品编码已存在',
    'item_name_required' => '请输入物品名称',
    'unit_required' => '请输入单位',
    'stock_in_date_required' => '请选择入库日期',
    'stock_out_date_required' => '请选择出库日期',
    'out_type_required' => '请选择出库类型',
    'unit_price_required' => '请输入单价',
    'batch_expiry_required' => '启用有效期管理的物品必须录入批次号和有效期',
    'price_deviation_warning' => '进价偏差超过20%，请确认',
    'insufficient_stock' => ':item 库存不足',
    'cannot_edit_confirmed' => '无法编辑已确认的记录',
    'cannot_delete_confirmed' => '无法删除已确认的记录',
    'cannot_confirm' => '无法确认此记录',
    'cannot_cancel' => '无法取消此记录',
    'no_items_to_confirm' => '没有可确认的明细',

    // 操作
    'add_item' => '添加物品',
    'add_category' => '添加分类',
    'create_stock_in' => '新建入库单',
    'create_stock_out' => '新建出库单',
    'confirm' => '确认',
    'cancel_record' => '取消',
    'view_details' => '查看详情',
    'configure_consumables' => '耗材设置',

    // 报表标题
    'low_stock_warning' => '库存不足预警',
    'expiry_warning' => '有效期预警',
    'select_category' => '选择分类',
    'select_supplier' => '选择供应商',
    'select_service' => '选择服务项目',
    'select_item' => '选择物品',
    'select_type' => '选择类型',
    'filter' => '筛选',
    'items_count' => '物品数量',

    // 表头
    'sn' => '序号',
    'action' => '操作',
    'added_by' => '录入人',
    'created_at' => '创建时间',
];
