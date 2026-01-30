<?php

return [
    'title' => '提成规则',
    'rule_form' => '提成规则表单',

    // Fields
    'rule_name' => '规则名称',
    'mode' => '提成模式',
    'rate' => '比例/金额',
    'service' => '服务项目',
    'branch' => '分院',
    'service_type' => '服务类型',
    'specific_service' => '指定服务',
    'base_rate' => '基础提成比例',
    'bonus_amount' => '奖金金额',

    // Modes
    'select_mode' => '选择提成模式',
    'mode_fixed_percentage' => '固定比例',
    'mode_tiered' => '阶梯提成',
    'mode_fixed_amount' => '固定金额',
    'mode_mixed' => '混合模式（基础+阶梯）',
    'tiered_rate' => '阶梯比例',

    // Tier Settings
    'tier_settings' => '阶梯设置',
    'tier1_threshold' => '第一阶梯门槛',
    'tier1_rate' => '第一阶梯比例',
    'tier2_threshold' => '第二阶梯门槛',
    'tier2_rate' => '第二阶梯比例',
    'tier3_threshold' => '第三阶梯门槛',
    'tier3_rate' => '第三阶梯比例',

    // Placeholders
    'service_type_placeholder' => '例如：口腔、综合等',
    'all_services' => '全部服务',
    'all_branches' => '全部分院',

    // Validation
    'name_required' => '请输入规则名称',
    'mode_required' => '请选择提成模式',

    // Messages
    'added_successfully' => '提成规则添加成功',
    'updated_successfully' => '提成规则更新成功',
    'deleted_successfully' => '提成规则删除成功',
    'delete_confirm' => '确定要删除此提成规则吗？',
    'no_rule_found' => '未找到该服务的提成规则',
];
