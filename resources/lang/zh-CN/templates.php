<?php

return [
    // Medical Templates
    'medical_templates' => '病历模板',
    'template' => '模板',
    'templates' => '模板',
    'create_template' => '创建模板',
    'edit_template' => '编辑模板',
    'delete_confirm_message' => '确定要删除此模板吗？',
    'preview' => '预览',

    // Template fields
    'name' => '名称',
    'code' => '代码',
    'code_hint' => '快捷代码（如：jieya）',
    'category' => '分类',
    'type' => '类型',
    'department' => '科室',
    'description' => '描述',
    'description_hint' => '模板的简短描述',
    'content' => '内容',
    'content_hint' => '模板内容',
    'content_help' => '请在下方各字段中填写模板内容',
    'simple_content_hint' => '请输入模板内容',
    'usage_count' => '使用次数',

    // SOAP Fields
    'subjective' => '主诉',
    'subjective_hint' => '患者自述的症状、不适、就诊原因等',
    'objective' => '检查',
    'objective_hint' => '医生检查发现，如：牙龈状态、龋坏程度、X光发现等',
    'assessment' => '评估',
    'assessment_hint' => '诊断结论，如：慢性牙龈炎、中度龋齿等',
    'plan' => '计划',
    'plan_hint' => '治疗计划和医嘱，如：超声波洁治、树脂充填等',

    // Categories
    'system' => '系统',
    'personal' => '个人',
    'all_categories' => '全部分类',

    // Types
    'progress_note' => '病程记录',
    'diagnosis' => '诊断',
    'treatment_plan' => '治疗计划',
    'chief_complaint' => '主诉',
    'all_types' => '全部类型',

    // Quick Phrases
    'quick_phrases' => '常用短语',
    'phrase' => '短语',
    'phrases' => '短语',
    'create_phrase' => '创建短语',
    'edit_phrase' => '编辑短语',
    'delete_phrase_confirm' => '确定要删除此短语吗？',
    'shortcut' => '快捷码',
    'shortcut_hint' => '简短代码（如：tzy 对应 探诊(+)）',
    'phrase_hint' => '完整短语文本',
    'scope' => '范围',
    'all_scopes' => '全部范围',

    // Phrase categories
    'examination' => '检查',
    'treatment' => '治疗',
    'other' => '其他',

    // Search & picker
    'search_templates' => '搜索模板...',
    'no_templates_found' => '未找到模板',
    'type_slash_to_search' => '输入 / 搜索模板，或从下拉框选择',
    'use_template' => '使用模板',
    'select_template' => '选择模板...',

    // Preview display
    'preview_title' => '模板预览',
    'no_content' => '暂无内容',
    // 学科分类树（与上面的 category「归属范围」不是一回事，见 TemplateCategory）
    'category_tree'           => '模板分类',
    'category_all'            => '全部分类',
    'category_uncategorized'  => '未归类',
    'category_add_root'       => '新增一级分类',
    'category_add_child'      => '新增下级',
    'category_rename'         => '重命名',
    'category_delete'         => '删除分类',
    'category_name'           => '分类名称',
    'category_of_template'    => '学科分类',
    'category_none'           => '不归类',
    'category_created'        => '分类已新增',
    'category_updated'        => '分类已更新',
    'category_deleted'        => '分类已删除',
    'category_not_found'      => '找不到这个分类',
    'category_parent_missing' => '上级分类不存在',
    'category_too_deep'       => '分类最多 :max 级',
    'category_cycle'          => '不能把分类挂到它自己的下级里',
    'category_has_children'   => '这个分类下面还有子分类，请先处理子分类',
    'category_has_templates'  => '这个分类下面还有模板，请先把模板移走',
    'category_delete_confirm' => '删除这个分类？',
];
