<?php

return [
    // Page titles and navigation
    'page_title' => '病例管理',
    'all_cases' => '所有病例',
    'add_case' => '添加病例',
    'edit_case' => '编辑病例',
    'view_case' => '查看病例',
    'case_information' => '病例信息',
    'search_placeholder' => '搜索病例编号、标题或患者...',
    'no_cases_found' => '暂无病例记录',
    'click_add_case_to_start' => '点击"添加"按钮创建第一个病例',

    // Case fields
    'case_no' => '病例编号',
    'title' => '标题',
    'patient' => '患者',
    'doctor' => '医生',
    'case_date' => '就诊日期',
    'status' => '状态',
    'chief_complaint' => '主诉',
    'history_of_present_illness' => '现病史',
    'closing_notes' => '结案备注',
    'added_by' => '录入人',

    // Case status
    'status_open' => '待处理',
    'status_in_progress' => '进行中',
    'status_closed' => '已结案',
    'status_follow_up' => '待复诊',

    // Select options
    'select_patient' => '选择患者',
    'select_doctor' => '选择医生',

    // Diagnoses section
    'diagnoses' => '诊断',
    'add_diagnosis' => '添加诊断',
    'edit_diagnosis' => '编辑诊断',
    'diagnosis_name' => '诊断名称',
    'icd_code' => 'ICD编码',
    'diagnosis_date' => '诊断日期',
    'severity' => '严重程度',
    'resolved_date' => '康复日期',
    'notes' => '备注',

    // Diagnosis status
    'diagnosis_status_active' => '活动',
    'diagnosis_status_resolved' => '已康复',
    'diagnosis_status_chronic' => '慢性',

    // Severity levels
    'select_severity' => '选择严重程度',
    'severity_mild' => '轻度',
    'severity_moderate' => '中度',
    'severity_severe' => '重度',

    // Progress Notes section
    'progress_notes' => '病程记录 (SOAP)',
    'add_progress_note' => '添加病程记录',
    'edit_progress_note' => '编辑病程记录',
    'view_progress_note' => '查看病程记录',
    'note_date' => '记录日期',
    'note_type' => '记录类型',

    // Note types
    'note_type_soap' => 'SOAP病程记录',
    'note_type_general' => '一般记录',
    'note_type_follow_up' => '复诊记录',

    // SOAP fields
    'soap_explanation' => 'SOAP病历格式',
    'soap_description' => 'SOAP是一种结构化的病历记录格式：主观资料（患者主诉）、客观资料（临床检查发现）、评估（诊断/评价）、计划（治疗方案）。',
    'subjective' => '主观资料 (S)',
    'objective' => '客观资料 (O)',
    'assessment' => '评估 (A)',
    'plan' => '计划 (P)',
    'subjective_help' => '患者自述的症状、主诉和病史',
    'objective_help' => '可测量、可观察的临床检查结果',
    'assessment_help' => '基于S和O的临床诊断或评价',
    'plan_help' => '治疗方案、用药、手术、随访计划',
    'subjective_placeholder' => '患者自述...',
    'objective_placeholder' => '体格检查显示...',
    'assessment_placeholder' => '诊断评估...',
    'plan_placeholder' => '治疗计划...',

    // Treatment Plans section
    'treatment_plans' => '治疗计划',
    'add_treatment_plan' => '添加治疗计划',
    'edit_treatment_plan' => '编辑治疗计划',
    'view_treatment_plan' => '查看治疗计划',
    'plan_name' => '计划名称',
    'description' => '描述',
    'planned_procedures' => '计划操作',
    'planned_procedures_placeholder' => '列出将要执行的操作...',
    'estimated_cost' => '预估费用',
    'actual_cost' => '实际费用',
    'start_date' => '开始日期',
    'target_completion_date' => '目标完成日期',
    'actual_completion_date' => '实际完成日期',
    'completion_notes' => '完成备注',
    'priority' => '优先级',

    // Plan status
    'plan_status_planned' => '已计划',
    'plan_status_in_progress' => '进行中',
    'plan_status_completed' => '已完成',
    'plan_status_cancelled' => '已取消',

    // Priority levels
    'priority_low' => '低',
    'priority_medium' => '中',
    'priority_high' => '高',
    'priority_urgent' => '紧急',

    // Vital Signs section
    'vital_signs' => '生命体征',
    'add_vital_sign' => '添加生命体征',
    'edit_vital_sign' => '编辑生命体征',
    'recorded_at' => '记录时间',
    'blood_pressure' => '血压',
    'systolic' => '收缩压',
    'diastolic' => '舒张压',
    'heart_rate' => '心率',
    'temperature' => '体温',
    'respiratory_rate' => '呼吸频率',
    'oxygen_saturation' => '血氧饱和度',
    'weight' => '体重',
    'height' => '身高',
    'cardiovascular' => '心血管',
    'general_measurements' => '一般测量',

    // Related appointments
    'related_appointments' => '相关预约',
    'appointment_no' => '预约编号',
    'appointment_date' => '预约日期',

    // Success messages
    'case_created_successfully' => '病例创建成功',
    'case_updated_successfully' => '病例更新成功',
    'case_deleted_successfully' => '病例删除成功',
    'diagnosis_added_successfully' => '诊断添加成功',
    'diagnosis_updated_successfully' => '诊断更新成功',
    'diagnosis_deleted_successfully' => '诊断删除成功',
    'progress_note_added_successfully' => '病程记录添加成功',
    'progress_note_updated_successfully' => '病程记录更新成功',
    'progress_note_deleted_successfully' => '病程记录删除成功',
    'treatment_plan_added_successfully' => '治疗计划添加成功',
    'treatment_plan_updated_successfully' => '治疗计划更新成功',
    'treatment_plan_deleted_successfully' => '治疗计划删除成功',
    'vital_sign_added_successfully' => '生命体征记录成功',
    'vital_sign_updated_successfully' => '生命体征更新成功',
    'vital_sign_deleted_successfully' => '生命体征删除成功',

    // Confirmation messages
    'confirm_delete' => '确定要删除吗？',
    'confirm_delete_message' => '删除后将无法恢复此病例！',
    'confirm_delete_diagnosis' => '删除后将无法恢复此诊断！',
    'confirm_delete_progress_note' => '删除后将无法恢复此病程记录！',
    'confirm_delete_treatment_plan' => '删除后将无法恢复此治疗计划！',
    'confirm_delete_vital_sign' => '删除后将无法恢复此生命体征记录！',

    // F-MED-001: Medical Record Edit Form
    'medical_record_edit' => '病历编辑',
    'visit_information' => '就诊信息',
    'visit_date' => '就诊日期',
    'visit_type' => '就诊类型',
    'initial_visit' => '初诊',
    'revisit' => '复诊',
    'attending_doctor' => '接诊医生',

    // SOAP Form Sections
    'chief_complaint_section' => '主诉',
    'chief_complaint_hint' => '描述患者主观感受，10-500字符',
    'present_illness_section' => '现病史',
    'present_illness_hint' => '疾病发生、发展过程',
    'examination_section' => '检查',
    'examination_hint' => '客观检查发现',
    'related_teeth' => '关联牙位',
    'select_teeth' => '选择牙位',
    'auxiliary_exam_section' => '辅助检查',
    'auxiliary_exam_hint' => 'X光、CT等检查结果',
    'select_images' => '选择影像',
    'diagnosis_section' => '诊断',
    'diagnosis_hint' => '诊断结论',
    'icd10_code' => 'ICD-10编码',
    'search_icd10' => '搜索ICD-10诊断编码',
    'treatment_section' => '治疗',
    'treatment_hint' => '治疗操作记录',
    'treatment_services' => '治疗项目',
    'add_service' => '添加项目',
    'medical_orders_section' => '医嘱',
    'medical_orders_hint' => '术后注意事项',

    // Follow-up Section
    'followup_section' => '复诊安排',
    'next_visit_date' => '下次复诊',
    'next_visit_note' => '复诊说明',
    'auto_create_followup' => '自动创建复诊提醒',

    // Right Panel
    'patient_info' => '患者信息',
    'patient_allergy' => '过敏',
    'patient_medical_history' => '病史',
    'patient_medication' => '用药',
    'tooth_chart' => '牙位图',
    'quick_tooth_select' => '快捷视图',
    'click_to_select_tooth' => '点击可选择牙位',
    'history_records' => '历史病历',
    'expand' => '展开',
    'collapse' => '收起',
    'quick_phrases' => '快捷短语',

    // Template
    'insert_template' => '插入模板',
    'system_templates' => '系统模板',
    'department_templates' => '科室模板',
    'my_templates' => '我的模板',
    'type_slash_for_template' => '输入 / 触发模板选择',

    // Actions
    'save_draft' => '保存草稿',
    'submit_record' => '提交',
    'draft_status' => '草稿',
    'draft_saved' => '草稿已保存',
    'record_submitted' => '病历已提交',

    // Quality Control
    'quality_check' => '质控检查',
    'qc_chief_complaint' => '主诉完整性',
    'qc_diagnosis_standard' => '诊断规范性',
    'qc_teeth_clarity' => '牙位明确性',
    'qc_treatment_link' => '治疗关联性',
    'qc_signature' => '签名完整',
    'qc_error' => '错误',
    'qc_warning' => '警告',
    'qc_chief_complaint_rule' => '主诉不能为空且≥10字符',
    'qc_diagnosis_rule' => '诊断需关联ICD-10编码',
    'qc_teeth_rule' => '涉及牙齿的治疗需明确牙位',
    'qc_treatment_rule' => '治疗内容需关联收费项目',
    'qc_signature_rule' => '需有医生电子签名',

    // Edit Permissions & Amendments
    'edit_within_24h' => '提交后24小时内可修改',
    'edit_requires_approval' => '病历已锁定，修改需填写原因并提交审批',
    'modification_reason' => '修改原因',
    'record_locked' => '病历已锁定',
    'amendment_submitted' => '修改申请已提交，等待审批',
    'amendment_approved' => '修改申请已通过',
    'amendment_rejected' => '修改申请已驳回',
    'amendment_already_reviewed' => '该修改申请已处理',
    'amendments' => '修改申请',
    'amendment_reason' => '修改原因',
    'amendment_status' => '审批状态',
    'amendment_pending' => '待审批',
    'amendment_review_notes' => '审批意见',
    'amendment_requested_by' => '申请人',
    'amendment_approved_by' => '审批人',
    'amendment_reviewed_at' => '审批时间',
    'approve_amendment' => '批准',
    'reject_amendment' => '驳回',
    'reject_reason_required' => '驳回时需填写审批意见',
    'version_history' => '版本历史',
    'version_number' => '版本号',
    'signature_hint' => '请在下方区域手写签名',
    'signature_required' => '提交病历需要医师签名',
    'signature_saved' => '签名已保存',
    'export_pdf' => '导出 PDF',
    'signed' => '已签名',
    'pdf_watermark' => '电子病历 - 仅供医疗使用',
    'pdf_archived' => 'PDF 已归档存储',

    // Validation
    'chief_complaint_required' => '请填写主诉',
    'chief_complaint_min' => '主诉至少需要10个字符',
    'examination_required' => '请填写检查',
    'diagnosis_required' => '请填写诊断',
    'treatment_required' => '请填写治疗',

    // Patient Selection (Create Mode)
    'select_patient_first' => '选择患者',
    'search_and_select_patient' => '搜索并选择患者...',
    'continue_to_record' => '继续填写病历',
    'create_new_patient' => '新建患者',
    'create_patient_hint' => '在新窗口中打开患者管理页面，创建后返回此页刷新',
    'no_patient_selected' => '未选择患者',
    'please_select_patient' => '请先选择患者',
    'select_patient_hint' => '请在右侧边栏选择患者后开始填写病历',

    // Print related
    'medical_record' => '病历记录',
    'soap_section' => 'SOAP病历',
    'examination' => '检查',
    'diagnosis' => '诊断',
    'treatment' => '治疗',
    'examination_teeth' => '检查牙位',
    'auxiliary_examination' => '辅助检查',
    'medical_orders' => '医嘱',
    'next_visit' => '复诊安排',
    'doctor_signature' => '医生签名',
    'date' => '日期',
    'bmi' => '体质指数',
    'visit_type_initial' => '初诊',
    'visit_type_revisit' => '复诊',
    'visit_type_follow_up' => '随访',
    'plan_status_planned' => '计划中',
    'plan_status_in_progress' => '进行中',
    'plan_status_completed' => '已完成',
    'plan_status_cancelled' => '已取消',

    // Related appointments
    'related_appointments' => '相关预约',
    'appointment_no' => '预约编号',
    'appointment_date' => '预约日期',

    // Teeth management
    'add_teeth' => '添加牙位',
    'click_tooth_to_select' => '点击选择牙位',
    'tooth_chart_hint' => '点击选择/取消牙位',
    'tooth_target_related' => '目标：关联牙位',
    'tooth_target_examination' => '目标：检查牙位',
    'no_history_records' => '暂无历史病历',
    'visit_record' => '就诊记录',
    'chronic_diseases' => '慢性病史',

    // Auxiliary section
    'auxiliary_section' => '辅助检查',
    'auxiliary_hint' => 'X光、CT等检查结果',
    'auxiliary_placeholder' => '描述辅助检查结果...',
    'attach_images' => '附加影像资料',

    // Examination section
    'examination_placeholder' => '描述体格检查和口腔检查发现...',

    // Diagnosis section
    'diagnosis_placeholder' => '填写诊断结论...',

    // Treatment section
    'treatment_placeholder' => '描述治疗操作过程...',

    // Follow-up section
    'followup_date' => '复诊日期',
    'followup_type' => '复诊方式',
    'followup_notes' => '复诊说明',
    'followup_notes_placeholder' => '填写复诊注意事项...',
    'followup_phone' => '电话随访',
    'followup_sms' => '短信提醒',
    'followup_visit' => '门诊复诊',
    'send_reminder' => '发送复诊提醒',

    // Visit type
    'visit_type_emergency' => '急诊',

    // Template buttons
    'template_cleaning' => '洁牙模板',
    'template_extraction' => '拔牙模板',
    'template_filling' => '补牙模板',

    // Quick phrases
    'phrase_probe_normal' => '探诊正常，牙周袋深度3mm以内',
    'phrase_probe_normal_short' => '探诊正常',
    'phrase_gum_bleeding' => '牙龈红肿，探诊出血',
    'phrase_gum_bleeding_short' => '牙龈出血',
    'phrase_calculus' => '可见龈上/龈下结石',
    'phrase_calculus_short' => '牙结石',
    'phrase_cavity' => '可见龋洞，探诊敏感',
    'phrase_cavity_short' => '龋齿',
    'phrase_sensitivity' => '冷热刺激敏感',
    'phrase_sensitivity_short' => '敏感',
    'phrase_mobility' => '牙齿松动度I/II/III度',
    'phrase_mobility_short' => '松动',
    'phrase_percussion_pain' => '叩诊疼痛(+)',
    'phrase_percussion_short' => '叩痛',
    'phrase_xray_normal' => 'X光片显示根尖周正常',
    'phrase_xray_normal_short' => 'X光正常',

    // Picker hints
    'hint_template_picker' => '文本框中输入 :key 选择病例模板',
    'hint_phrase_picker' => '文本框中输入 :key 选择常用短语',

    // Slash command menu
    'select_template' => '选择模板（输入关键字筛选）',
    'navigate' => '导航',
    'select' => '选择',
    'close' => '关闭',
    'no_matching_template' => '无匹配模板',

    // Template: Cleaning (洁牙)
    'tpl_cleaning_title' => '洁牙',
    'tpl_cleaning_desc' => '常规洁牙/牙周护理',
    'tpl_cleaning_chief' => '患者要求洁牙，自觉牙龈出血、口腔异味',
    'tpl_cleaning_exam' => '牙龈红肿，探诊出血(+)，可见龈上结石及色素沉着，牙周袋深度3-4mm',
    'tpl_cleaning_diag' => '慢性牙龈炎',
    'tpl_cleaning_treat' => '超声龈上洁治，抛光，冲洗上药',
    'tpl_cleaning_orders' => '1. 24小时内勿进食过冷过热食物\\n2. 可能出现短暂牙齿敏感，属正常现象\\n3. 建议每半年洁牙一次',

    // Template: Extraction (拔牙)
    'tpl_extraction_title' => '拔牙',
    'tpl_extraction_desc' => '牙齿拔除术',
    'tpl_extraction_chief' => '患者要求拔除___牙，该牙反复疼痛/松动/无法保留',
    'tpl_extraction_exam' => '___牙残根/残冠/松动III度，叩痛(+)，牙龈红肿',
    'tpl_extraction_diag' => '___牙残根/残冠/慢性根尖周炎',
    'tpl_extraction_treat' => '局麻下拔除___牙，拔牙窝搔刮，明胶海绵填塞，咬纱布压迫止血',
    'tpl_extraction_orders' => '1. 咬紧纱布30-60分钟后吐出\\n2. 24小时内勿刷拔牙区、勿用力漱口\\n3. 2小时后可进温凉软食\\n4. 如有持续出血请及时就诊',

    // Template: Filling (补牙)
    'tpl_filling_title' => '补牙',
    'tpl_filling_desc' => '龋齿充填治疗',
    'tpl_filling_chief' => '患者主诉___牙有洞，进食嵌塞/冷热刺激敏感',
    'tpl_filling_exam' => '___牙可见龋洞，探诊敏感，叩痛(-)，冷热诊敏感/正常',
    'tpl_filling_diag' => '___牙中龋/深龋',
    'tpl_filling_treat' => '去腐备洞，垫底/直接充填，树脂/玻璃离子充填，调牙合抛光',
    'tpl_filling_orders' => '1. 2小时内勿进食\\n2. 避免用患侧咬硬物\\n3. 如有持续疼痛请复诊',

    // Template: Root Canal (根管治疗)
    'tpl_rootcanal_title' => '根管治疗',
    'tpl_rootcanal_desc' => '牙髓炎/根尖周炎治疗',
    'tpl_rootcanal_chief' => '患者主诉___牙自发痛/夜间痛/咬合痛，持续___天',
    'tpl_rootcanal_exam' => '___牙可见龋坏/充填体，探诊穿髓孔，叩痛(+)，冷热诊迟钝/无反应，根尖区压痛(+/-)',
    'tpl_rootcanal_diag' => '___牙急性/慢性牙髓炎/根尖周炎',
    'tpl_rootcanal_treat' => '局麻下开髓，拔髓，根管预备，根管冲洗，根管内封药/根管充填',
    'tpl_rootcanal_orders' => '1. 可能出现术后反应性疼痛，一般2-3天缓解\\n2. 避免用患侧咬硬物\\n3. 按时复诊完成后续治疗\\n4. 根管治疗后建议做冠修复',

    // Template: Periodontal (牙周治疗)
    'tpl_periodontal_title' => '牙周治疗',
    'tpl_periodontal_desc' => '牙周病系统治疗',
    'tpl_periodontal_chief' => '患者主诉牙龈出血、牙齿松动、口腔异味',
    'tpl_periodontal_exam' => '牙龈红肿退缩，探诊出血(+)，牙周袋深度___mm，可见龈下结石，牙齿松动I-II度',
    'tpl_periodontal_diag' => '慢性牙周炎（轻/中/重度）',
    'tpl_periodontal_treat' => '龈上洁治，龈下刮治（___象限），根面平整，牙周冲洗上药',
    'tpl_periodontal_orders' => '1. 可能出现牙齿敏感，属正常现象\\n2. 使用软毛牙刷，掌握正确刷牙方法\\n3. 配合使用牙线/牙缝刷\\n4. 按时复诊，定期维护',
];
