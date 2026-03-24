<?php

return [
    'title' => '诊疗项目',
    'procedure_form' => '项目表单',
    'procedure' => '项目',
    'price' => '价格',
    'enter_procedure' => '输入项目名称',
    'enter_price' => '输入价格',
    'add_by' => '录入人',

    'clinical_services_added_successfully' => '项目添加成功',
    'clinical_services_updated_successfully' => '项目更新成功',
    'clinical_services_deleted_successfully' => '项目删除成功',
    'delete_confirm_message' => '您将无法恢复此项目！',

    // 大类管理
    'service_categories'            => '收费大类',
    'category_name'                 => '大类名称',
    'category_sort_order'           => '排序',
    'category_is_active'            => '启用',
    'category_created_successfully' => '大类创建成功',
    'category_updated_successfully' => '大类更新成功',
    'category_deleted_successfully' => '大类删除成功',
    'category_name_duplicate'       => '大类名称已存在',

    // 套餐管理
    'service_packages'              => '收费套餐',
    'package_name'                  => '套餐名称',
    'package_total_price'           => '套餐总价',
    'package_description'           => '套餐说明',
    'package_items'                 => '套餐明细',
    'package_item_qty'              => '数量',
    'package_item_price'            => '套餐内单价',
    'package_created_successfully'  => '套餐创建成功',
    'package_updated_successfully'  => '套餐更新成功',
    'package_deleted_successfully'  => '套餐删除成功',

    // 批量改价
    'batch_update_price'            => '批量改价',
    'batch_mode_percent'            => '按百分比调整',
    'batch_mode_fixed'              => '按固定金额调整',
    'batch_value'                   => '调整值',
    'batch_scope_all'               => '全部项目',
    'batch_scope_category'          => '仅当前大类',

    // 项目字段标签
    'name'                          => '项目名称',
    'is_active'                     => '状态',
    'is_discountable'               => '允许打折',
    'is_favorite'                   => '常用项目',
    'unit'                          => '单位',
    'description'                   => '说明',
    'service_items'                 => '收费项目',
    'add_item'                      => '添加明细',

    // 导入
    'download_import_template'      => '下载导入模板',
    'import_template_hint'          => '请按模板格式填写，name（必填）、price、unit、category 列',
    'select_file'                   => '选择文件',
    'start_import'                  => '开始导入',
    'import_success'                => '成功导入 :count 条记录',
    'import_failed_rows'            => '导入失败：:rows',
    'batch_update_success'          => '已更新 :count 条记录',

    // 批量改价
    'batch_price_scope_hint'        => '将批量调整符合条件的收费项目价格',
    'batch_mode'                    => '调整方式',
    'batch_value_placeholder'       => '输入调整值',
    'confirm_price_change'          => '确认改价',
];