<?php

return [
    'title'               => '今日工作',
    'today_patients'      => '今日就诊',
    'today_doctors'       => '今日出诊',
    'today_revisits'      => '今日回访',
    'today_appointments'  => '今日预约',
    'today_receivable'    => '今日应收（千元）',
    'today_collected'     => '今日实收（千元）',

    'not_arrived'         => '预约未到',
    'waiting'             => '候诊中',
    'called'              => '已叫号',
    'in_treatment'        => '就诊中',
    'completed'           => '已完成',
    'no_show'             => '未到诊',

    'new_patient'         => '新患者登记',
    'new_appointment'     => '新预约',
    'display_screen'      => '叫号大屏',
    'check_in'            => '签到',
    'call'                => '叫号',
    'recall'              => '重呼',
    'start_treatment'     => '开始就诊',
    'complete_treatment'  => '完成就诊',
    'medical_case'        => '开病历',
    'prescription'        => '开处方',
    'invoice'             => '收费',
    'next_appointment'    => '约下次',
    'mark_no_show'        => '标记未到',
    'mark_no_show_success'=> '已标记为未到诊',
    'search_patient'      => '搜索患者',
    'all'                 => '全部',
    'filter_status'       => '按状态筛选',
    'persons'             => '人',
    'confirm_no_show'     => '确认标记该患者为未到诊？',
    'confirm_cancel'      => '确认取消排队？',
    'select_chair'        => '选择椅位',
    'select_chair_hint'   => '请选择诊疗椅位',

    // 看板视图
    'table_view'          => '表格视图',
    'kanban_view'         => '看板视图',
    'toggle_collapse'     => '折叠/展开卡片',
    'kanban_empty'        => '暂无患者',
    'revisit'             => '复诊',
    'first_visit'         => '初诊',
    'minutes'             => '分钟',
    'hours'               => '小时',

    // 患者侧栏
    'drawer_visits'       => '就诊记录',
    'drawer_billing'      => '收费记录',
    'view_full_detail'    => '查看完整详情',
    'years_old'           => '岁',
    'no_records'          => '暂无记录',
    'total'               => '应收',
    'paid'                => '已收',

    // 信息维度 Tab
    'tab_today_work'     => '今日工作',
    'tab_billing'        => '今日对账',
    'tab_followups'      => '今日回访',
    'tab_tomorrow'       => '明日预约',
    'tab_week_missed'    => '一周失约',
    'tab_birthdays'      => '今日生日',
    'tab_doctor_table'   => '医生表',

    // 今日对账
    'billing_method'     => '支付方式',
    'billing_count'      => '笔数',
    'billing_amount'     => '金额',
    'billing_total'      => '合计',
    'billing_no_data'    => '今日暂无收款记录',

    // 今日回访
    'followup_type'      => '回访类型',
    'followup_status'    => '回访状态',
    'followup_purpose'   => '回访目的',
    'followup_pending'   => '待回访',
    'followup_completed' => '已回访',
    'followup_no_data'   => '今日无回访安排',

    // 明日预约
    'tomorrow_no_data'   => '明日暂无预约',

    // 一周失约
    'missed_date'        => '预约日期',
    'missed_no_data'     => '近7日无失约记录',

    // 今日生日
    'birthday_age'       => '年龄',
    'birthday_no_data'   => '今日无生日患者',
    'birthday_wish'      => '祝福',

    // 医生表
    'doctor_total'       => '总预约',
    'doctor_waiting'     => '候诊',
    'doctor_treating'    => '就诊中',
    'doctor_done'        => '已完成',
    'doctor_patients'    => '患者列表',
    'doctor_no_data'     => '今日无医生排班',

    // 工具栏筛选
    'filter_all_statuses' => '全部状态',
    'filter_all_doctors'  => '所有医生',
    'filter_date'         => '日期',

    // 新增 Tab
    'tab_paid'              => '今日已收款',
    'tab_unpaid'            => '今日待收款',
    'tab_lab_cases'         => '外加工查询',

    // 今日已收款
    'paid_no_data'          => '今日暂无收款',
    'paid_count_unit'       => '笔',
    'invoice_no'            => '账单编号',

    // 今日待收款
    'unpaid_no_data'        => '今日暂无待收款',
    'unpaid_outstanding'    => '欠款',

    // 外加工查询
    'lab_cases_no_data'     => '今日无外加工件到达',
    'lab_case_no'           => '加工单号',
    'lab_prosthesis_type'   => '修复类型',
    'lab_material'          => '材料',
    'lab_name'              => '加工厂',
    'lab_expected_date'     => '预计返回',
    'lab_actual_date'       => '实际返回',
    'lab_status_pending'       => '待送出',
    'lab_status_sent'          => '已送出',
    'lab_status_in_production' => '制作中',
    'lab_status_returned'      => '已返回',
    'lab_status_try_in'        => '试戴',
    'lab_status_completed'     => '完成',
    'lab_status_rework'        => '返工',

    // 增强筛选
    'filter_all_types'         => '所有类型',
    'filter_start_date'        => '开始',
    'filter_end_date'          => '结束',
    'followup_type_phone'      => '电话',
    'followup_type_sms'        => '短信',
    'followup_type_email'      => '邮件',
    'followup_type_visit'      => '上门',
    'followup_type_other'      => '其他',
    'followup_cancelled'       => '已取消',
    'followup_no_response'     => '未应答',
    'search_patient_hint'      => '姓名/拼音/手机号/病历号',
];
