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
    'patients' => '患者',
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

    // Patient Information
    'patient_id' => '患者编号',
    'patient_number' => '患者号',
    'registration_date' => '登记日期',
    'first_name' => '名',
    'last_name' => '姓',
    'full_name' => '全名',
    'date_of_birth' => '出生日期',
    'age' => '年龄',
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

    // Emergency Contact
    'emergency_contact' => '紧急联系人',
    'emergency_contact_name' => '紧急联系人姓名',
    'emergency_contact_phone' => '紧急联系人电话',
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
    'view_lab_results' => '查看检验结果',
    'print_card' => '打印卡片',
    'send_sms' => '发送短信',
    'send_email' => '发送邮件',

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
    'no_patients_found' => '未找到患者。',
    'patient_already_exists' => '患者已存在。',

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
    'no_photo' => '暂无照片',

];
