<?php

return [

    /**
     * Prescriptions Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于处方管理。
     * 您可以根据应用程序的要求自由修改这些语言行。
     */

    // 页面标题
    'prescriptions' => '处方',
    'prescription' => '处方',
    'prescription_management' => '处方管理',
    'prescription_list' => '处方列表',
    'prescription_details' => '处方详情',
    'new_prescription' => '新建处方',
    'create_prescription' => '创建处方',
    'add_prescription' => '添加处方',
    'edit_prescription' => '编辑处方',
    'view_prescription' => '查看处方',
    'prescription_history' => '处方历史',

    // 表单标签
    'prescription_no' => '处方号',
    'prescription_number' => '处方编号',
    'prescription_date' => '处方日期',
    'patient_name' => '患者姓名',
    'patient_id' => '患者编号',
    'doctor_name' => '医生姓名',
    'prescribed_by' => '开具医生',
    'diagnosis' => '诊断',
    'complaints' => '主诉',
    'symptoms' => '症状',
    'notes' => '备注',
    'instructions' => '说明',
    'special_instructions' => '特殊说明',
    'follow_up' => '复诊',
    'follow_up_date' => '复诊日期',
    'valid_until' => '有效期至',
    'expiry_date' => '过期日期',

    // 药物详情
    'medications' => '药物',
    'medication' => '药物',
    'medicine_name' => '药品名称',
    'drug_name' => '药物名称',
    'generic_name' => '通用名',
    'brand_name' => '品牌名',
    'dosage' => '剂量',
    'dose' => '剂量',
    'strength' => '强度',
    'form' => '剂型',
    'route' => '给药途径',
    'frequency' => '频率',
    'duration' => '疗程',
    'quantity' => '数量',
    'refills' => '续配次数',
    'instructions_for_use' => '用药说明',

    // 剂型
    'tablet' => '片剂',
    'capsule' => '胶囊',
    'syrup' => '糖浆',
    'liquid' => '液体',
    'injection' => '注射剂',
    'ointment' => '软膏',
    'cream' => '乳膏',
    'gel' => '凝胶',
    'drops' => '滴剂',
    'inhaler' => '吸入剂',
    'powder' => '粉剂',
    'suspension' => '混悬液',

    // 给药途径
    'oral' => '口服',
    'topical' => '外用',
    'intravenous' => '静脉注射',
    'intramuscular' => '肌肉注射',
    'subcutaneous' => '皮下注射',
    'sublingual' => '舌下含服',
    'rectal' => '直肠给药',
    'inhalation' => '吸入',
    'ophthalmic' => '眼部用药',
    'otic' => '耳部用药',
    'nasal' => '鼻部用药',

    // 频率
    'once_daily' => '每日一次',
    'twice_daily' => '每日两次',
    'three_times_daily' => '每日三次',
    'four_times_daily' => '每日四次',
    'every_hour' => '每小时',
    'every_4_hours' => '每4小时',
    'every_6_hours' => '每6小时',
    'every_8_hours' => '每8小时',
    'every_12_hours' => '每12小时',
    'as_needed' => '必要时',
    'before_meals' => '饭前',
    'after_meals' => '饭后',
    'with_meals' => '随餐',
    'at_bedtime' => '睡前',
    'morning' => '早晨',
    'afternoon' => '下午',
    'evening' => '晚上',
    'night' => '夜间',

    // 疗程
    'days' => '天',
    'weeks' => '周',
    'months' => '月',
    'until_finished' => '用完为止',
    'ongoing' => '持续',
    'as_directed' => '遵医嘱',

    // 操作
    'print_prescription' => '打印处方',
    'download_prescription' => '下载处方',
    'email_prescription' => '邮件发送处方',
    'send_to_pharmacy' => '发送至药房',
    'add_medication' => '添加药物',
    'remove_medication' => '移除药物',
    'refill_prescription' => '续配处方',
    'renew_prescription' => '更新处方',
    'cancel_prescription' => '取消处方',
    'delete_prescription' => '删除处方',

    // 状态
    'status' => '状态',
    'active' => '活跃',
    'completed' => '已完成',
    'cancelled' => '已取消',
    'expired' => '已过期',
    'pending' => '待处理',
    'dispensed' => '已配药',
    'partially_dispensed' => '部分配药',

    // 表格表头
    'id' => '编号',
    'date' => '日期',
    'patient' => '患者',
    'doctor' => '医生',
    'no_of_items' => '项目数',
    'actions' => '操作',

    // 占位符
    'enter_prescription_no' => '输入处方号',
    'enter_patient_name' => '输入患者姓名',
    'enter_diagnosis' => '输入诊断',
    'enter_medicine_name' => '输入药品名称',
    'enter_dosage' => '输入剂量',
    'enter_quantity' => '输入数量',
    'select_patient' => '选择患者',
    'select_medicine' => '选择药品',
    'select_frequency' => '选择频率',
    'select_duration' => '选择疗程',

    // 消息
    'prescription_created_successfully' => '处方创建成功',
    'prescription_updated_successfully' => '处方更新成功',
    'prescription_deleted_successfully' => '处方删除成功',
    'prescription_printed_successfully' => '处方打印成功',
    'prescription_sent_successfully' => '处方发送成功',
    'prescription_cancelled_successfully' => '处方取消成功',
    'confirm_delete_prescription' => '您确定要删除此处方吗？',
    'confirm_cancel_prescription' => '您确定要取消此处方吗？',
    'prescription_not_found' => '未找到处方',
    'error_creating_prescription' => '创建处方时出错',
    'error_updating_prescription' => '更新处方时出错',
    'error_deleting_prescription' => '删除处方时出错',
    'no_prescriptions_found' => '未找到处方',
    'no_medications_added' => '未添加药物',

    // 搜索和筛选
    'search_prescriptions' => '搜索处方',
    'filter_prescriptions' => '筛选处方',
    'filter_by_status' => '按状态筛选',
    'filter_by_date' => '按日期筛选',
    'start_date' => '开始日期',
    'end_date' => '结束日期',

    // 警告和提醒
    'drug_allergy_warning' => '药物过敏警告',
    'drug_interaction_warning' => '药物相互作用警告',
    'patient_allergies' => '患者过敏史',
    'allergic_to' => '过敏源',
    'check_allergies' => '检查过敏史',
    'check_interactions' => '检查药物相互作用',

    // 附加信息
    'pharmacy_notes' => '药房备注',
    'prescriber_signature' => '开具医生签名',
    'dispensed_by' => '配药人',
    'dispensing_date' => '配药日期',
    'refill_count' => '续配次数',
    'remaining_refills' => '剩余续配次数',

];
