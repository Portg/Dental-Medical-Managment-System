<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Patient Management Language Lines
    |--------------------------------------------------------------------------
    |
    | Language lines for patient management module
    |
    */

    // Page Titles
    'patient' => '患者',
    'patient_management' => '患者管理',
    'patient_list' => '患者列表',
    'patient_details' => '患者详情',
    'patient_history' => '患者历史',
    'add_patient' => '添加患者',
    'edit_patient' => '编辑患者',
    'view_patient' => '查看患者',
    'patient_profile' => '患者资料',
    'patient_card' => '患者卡',
    'medical_card' => '医疗卡',
    'patient_form' => '患者表单',

    // Patient Information
    'patient_id' => '患者编号',
    'patient_number' => '患者号',
    'registration_date' => '登记日期',
    'first_name' => '名',
    'last_name' => '姓',
    'full_name' => '姓名',
    'date_of_birth' => '出生日期',
    'age' => '年龄',
    'age_placeholder' => '请输入年龄',
    'age_hint' => '可选，可根据出生日期自动计算',
    'gender' => '性别',
    'male' => '男',
    'female' => '女',
    'other' => '其他',
    'blood_group' => '血型',
    'marital_status' => '婚姻状况',
    'single' => '未婚',
    'married' => '已婚',
    'divorced' => '离婚',
    'widowed' => '丧偶',
    'nationality' => '国籍',
    'religion' => '宗教',
    'occupation' => '职业',
    'occupation_placeholder' => '请输入职业',
    'national_id' => '身份证号',
    'passport_number' => '护照号码',

    // Contact Information
    'contact_information' => '联系信息',
    'email' => '邮箱',
    'phone' => '电话',
    'mobile' => '手机',
    'home_phone' => '家庭电话',
    'work_phone' => '工作电话',
    'address' => '地址',
    'street_address' => '街道地址',
    'city' => '城市',
    'state' => '州/省',
    'country' => '国家',
    'postal_code' => '邮政编码',
    'zip_code' => '邮编',
    'place_of_work' => '工作单位',
    'alternative_phone_no' => '备用电话号码',

    // Emergency Contact
    'emergency_contact' => '紧急联系人',
    'emergency_contact_name' => '紧急联系人姓名',
    'next_of_kin_phone' => '紧急联系人电话',
    'next_of_kin_address' => '紧急联系人地址',
    'emergency_contact_relationship' => '与患者关系',
    'relationship' => '关系',
    'parent' => '父母',
    'spouse' => '配偶',
    'sibling' => '兄弟姐妹',
    'child' => '子女',
    'friend' => '朋友',
    'guardian' => '监护人',

    // Insurance Information
    'insurance_information' => '保险信息',
    'insurance_company' => '保险公司',
    'insurance_number' => '保险号',
    'insurance_policy_number' => '保险单号',
    'insurance_expiry_date' => '保险到期日期',
    'insurance_status' => '保险状态',
    'has_medical_insurance' => '患者有医疗保险',
    'has_insurance' => '有保险',
    'no_insurance' => '无保险',

    // Medical Information
    'medical_information' => '医疗信息',
    'allergies' => '过敏史',
    'chronic_diseases' => '慢性病',
    'current_medications' => '当前用药',
    'past_surgeries' => '既往手术',
    'family_history' => '家族史',
    'social_history' => '社会史',
    'smoking' => '吸烟',
    'alcohol' => '饮酒',
    'drugs' => '药物使用',
    'yes' => '是',
    'no' => '否',
    'occasionally' => '偶尔',
    'regularly' => '经常',

    // Appointment History
    'appointment_history' => '预约历史',
    'treatment_history' => '治疗历史',
    'visit_history' => '就诊历史',
    'last_visit' => '最后就诊',
    'next_appointment' => '下次预约',
    'total_visits' => '总就诊次数',
    'total_appointments' => '总预约次数',

    // Financial Information
    'financial_information' => '财务信息',
    'total_amount_paid' => '已付总额',
    'total_amount_due' => '应付总额',
    'outstanding_balance' => '未付余额',
    'payment_status' => '付款状态',
    'credit_limit' => '信用额度',

    // Patient Status
    'patient_status' => '患者状态',
    'active' => '活跃',
    'inactive' => '不活跃',
    'archived' => '已归档',
    'deceased' => '已故',
    'transferred' => '已转诊',

    // Actions
    'add_new_patient' => '添加新患者',
    'edit_patient_info' => '编辑患者信息',
    'delete_patient' => '删除患者',
    'archive_patient' => '归档患者',
    'restore_patient' => '恢复患者',
    'view_history' => '查看历史',
    'book_appointment' => '预约挂号',
    'create_invoice' => '创建发票',
    'view_invoices' => '查看发票',
    'view_prescriptions' => '查看处方',
    'filter_patients' => '筛选患者',

    // Search & Filter
    'search_patients' => '搜索患者',
    'filter_by' => '筛选',
    'filter_by_status' => '按状态筛选',
    'filter_by_date' => '按日期筛选',
    'filter_by_age' => '按年龄筛选',
    'filter_by_gender' => '按性别筛选',
    'show_all' => '显示全部',
    'show_active' => '显示活跃',
    'show_inactive' => '显示不活跃',

    // Messages
    'patient_added_successfully' => '患者添加成功！',
    'patient_updated_successfully' => '患者更新成功！',
    'patient_deleted_successfully' => '患者删除成功！',
    'patient_archived_successfully' => '患者归档成功！',
    'patient_restored_successfully' => '患者恢复成功！',
    'patient_not_found' => '未找到患者。',
    'confirm_delete_patient' => '您确定要删除此患者吗？',
    'confirm_archive_patient' => '您确定要归档此患者吗？',
    'patient_has_appointments' => '此患者有预约记录，无法删除。',
    'patient_has_invoices' => '此患者有发票记录，无法删除。',
    'error_adding_patient' => '添加患者时出错，请重试。',
    'error_updating_patient' => '更新患者时出错，请重试。',
    'error_deleting_patient' => '删除患者时出错，请重试。',
    'no_patients_found' => '暂无患者数据',
    'patient_already_exists' => '患者已存在。',
    'patient_no_history_treatment' => '此患者无治疗记录。',
    'delete_patient_warning' => '删除患者将永久移除其所有相关数据，无法恢复。请谨慎操作。',

    // Validation
    'first_name_required' => '名字为必填项。',
    'last_name_required' => '姓氏为必填项。',
    'email_required' => '邮箱为必填项。',
    'phone_required' => '电话为必填项。',
    'date_of_birth_required' => '出生日期为必填项。',
    'gender_required' => '性别为必填项。',
    'invalid_email' => '无效的邮箱地址。',
    'invalid_phone' => '无效的电话号码。',
    'invalid_date' => '无效的日期。',
    'email_already_taken' => '该邮箱已被使用。',
    'phone_already_taken' => '该电话号码已被使用。',

    // Notes
    'notes' => '备注',
    'add_note' => '添加备注',
    'edit_note' => '编辑备注',
    'delete_note' => '删除备注',
    'private_notes' => '私人备注',
    'public_notes' => '公共备注',

    // Documents
    'documents' => '文档',
    'upload_document' => '上传文档',
    'view_documents' => '查看文档',
    'no_documents' => '暂无文档',

    // Photos
    'photo' => '照片',
    'upload_photo' => '上传照片',
    'change_photo' => '更换照片',
    'remove_photo' => '删除照片',

    // 表格表头
    'id' => '编号',
    'surname' => '姓',
    'other_name' => '名',
    'dob' => '出生日期',
    'email_address' => '邮箱地址',
    'phone_no' => '联系电话',
    'contacts' => '联系方式',
    'next_of_kin' => '紧急联系人',
    'medical_aid' => '医疗保险',
    'insurance_provider' => '保险公司',
    'added_by' => '录入人',
    'action' => '操作',
    'status' => '状态',
    'confirm_delete_message' => '您将无法恢复此患者的信息！',
    'choose_insurance_company' => '选择保险公司...',
    'choose_patient' => '选择患者...',

    // Additional CRUD Messages
    'created_successfully' => '患者创建成功',
    'updated_successfully' => '患者更新成功',
    'deleted_successfully' => '患者删除成功',
    'search' => '搜索',
    'search_patient' => '搜索患者',
    'reset' => '重置',
    'all' => '全部',
    'export' => '导出',
    'import' => '导入',
    'print' => '打印',
    'select_patient' => '选择患者',
    'patient_name' => '患者姓名',
    'medical_cards' => '医疗卡片',
    'medical_history' => '病历',

    // Patient Detail Page
    'patients' => '患者',
    'patient_no' => '患者编号',
    'name' => '姓名',
    'insurance' => '保险',
    'othername' => '名',
    'nin' => '身份证号',
    'profession' => '职业',
    'alternative_phone' => '备用电话',

    // Tab Names
    'basic_info' => '基本信息',
    'dental_chart' => '牙位图',
    'appointments' => '预约记录',
    'medical_cases' => '病例记录',
    'images' => '影像资料',
    'invoices' => '消费记录',
    'followups' => '随访记录',

    // Section Titles
    'personal_info' => '个人信息',
    'contact_info' => '联系信息',
    'insurance_info' => '保险信息',

    // Other
    'dental_chart_description' => '查看患者牙位图和历史记录',
    'view_dental_history' => '查看牙科历史',

    // Patient Tags and Sources
    'source' => '患者来源',
    'tags' => '标签',
    'medication_history' => '用药情况',
    'medication_history_hint' => '当前用药或药物过敏情况',
    'notes_hint' => '关于患者的其他备注',

    // Form - Additional keys
    'health_info' => '健康信息',
    'other_info' => '其他信息',
    'id_card' => '身份证号',
    'id_card_placeholder' => '请输入18位身份证号',
    'id_card_hint' => '自动识别生日和性别',
    'drug_allergy' => '药物过敏',
    'drug_allergy_hint' => '如：青霉素、头孢、利多卡因等',
    'allergy_warning' => '该患者有过敏史，诊疗时请注意',
    'current_medication' => '当前用药',
    'current_medication_hint' => '请列出当前正在使用的药物',
    'special_conditions' => '特殊情况',
    'special_condition' => '特殊情况',
    'pregnant' => '怀孕',
    'breastfeeding' => '哺乳期',
    'is_pregnant' => '怀孕',
    'is_breastfeeding' => '哺乳期',

    // Drug Allergy Options (按表单设计规范 F-PAT-001)
    'allergy_penicillin' => '青霉素',
    'allergy_cephalosporin' => '头孢',
    'allergy_sulfa' => '磺胺',
    'allergy_anesthetic' => '麻醉药',
    'allergy_iodine' => '碘',
    'allergy_latex' => '乳胶',
    'other_allergy_placeholder' => '其他过敏药物，请注明...',

    // Systemic Diseases Options (按表单设计规范 F-PAT-001)
    'disease_hypertension' => '高血压',
    'disease_diabetes' => '糖尿病',
    'disease_heart' => '心脏病',
    'disease_hepatitis' => '肝炎',
    'disease_infectious' => '传染病',
    'disease_blood' => '血液病',
    'other_disease_placeholder' => '其他病史，请注明...',

    // Placeholders
    'email_placeholder' => '请输入邮箱地址',
    'address_placeholder' => '请输入详细地址',
    'place_of_work_placeholder' => '请输入工作单位',

    // Empty state (空状态)
    'click_add_patient_to_start' => '请点击「添加患者」开始创建',

    // Demographic Information (人口统计信息)
    'demographic_info' => '人口统计信息',
    'ethnicity' => '民族',
    'education' => '教育程度',
    'blood_type' => '血型',

    // Ethnicity Options (民族选项)
    'ethnicity_han' => '汉族',
    'ethnicity_zhuang' => '壮族',
    'ethnicity_hui' => '回族',
    'ethnicity_manchu' => '满族',
    'ethnicity_uyghur' => '维吾尔族',
    'ethnicity_miao' => '苗族',
    'ethnicity_yi' => '彝族',
    'ethnicity_tujia' => '土家族',
    'ethnicity_tibetan' => '藏族',
    'ethnicity_mongol' => '蒙古族',
    'ethnicity_dong' => '侗族',
    'ethnicity_bouyei' => '布依族',
    'ethnicity_yao' => '瑶族',
    'ethnicity_bai' => '白族',
    'ethnicity_korean' => '朝鲜族',
    'ethnicity_hani' => '哈尼族',
    'ethnicity_li' => '黎族',
    'ethnicity_kazak' => '哈萨克族',
    'ethnicity_dai' => '傣族',
    'ethnicity_she' => '畲族',
    'ethnicity_other' => '其他民族',

    // Marital Status Options (婚姻状况选项)
    'marital_single' => '未婚',
    'marital_married' => '已婚',
    'marital_divorced' => '离异',
    'marital_widowed' => '丧偶',
    'marital_other' => '其他',

    // Education Options (教育程度选项)
    'education_primary' => '小学',
    'education_junior_high' => '初中',
    'education_senior_high' => '高中/中专',
    'education_college' => '大专',
    'education_bachelor' => '本科',
    'education_master' => '硕士',
    'education_doctor' => '博士',
    'education_other' => '其他',

    // Blood Type Options (血型选项)
    'blood_type_a' => 'A型',
    'blood_type_b' => 'B型',
    'blood_type_ab' => 'AB型',
    'blood_type_o' => 'O型',
    'blood_type_a_rh_negative' => 'A型Rh阴性（熊猫血）',
    'blood_type_b_rh_negative' => 'B型Rh阴性（熊猫血）',
    'blood_type_ab_rh_negative' => 'AB型Rh阴性（熊猫血）',
    'blood_type_o_rh_negative' => 'O型Rh阴性（熊猫血）',
    'blood_type_unknown' => '未知',

    // Demographics section
    'demographics' => '人口统计',

    // Referral
    'referred_by' => '介绍人',
    'referred_by_placeholder' => '搜索患者姓名或电话...',

    // Kin Relations
    'kin_relations' => '亲友关系',
    'add_kin_relation' => '添加亲友',
    'kin_search_placeholder' => '搜索患者姓名或电话...',

    // Patient Groups
    'patient_group' => '客户分组',
    'group_walk_in' => '直客',
    'group_referral' => '转介绍',
    'group_orthodontics' => '正畸',
    'group_implant' => '种植',
    'group_pediatric' => '儿童',

    // Detail page summary bar
    'first_visit_doctor' => '首诊',
    'latest_visit' => '最新就诊',
    'total_spending' => '消费总额',
    'member_balance' => '会员余额',
    'member_points' => '积分',
    'no_visit_record' => '暂无就诊',
];