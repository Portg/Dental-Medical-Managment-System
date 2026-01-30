<?php

return [
    // Page title
    'title' => '候诊管理',
    'display_screen_title' => '候诊叫号大屏',

    // Actions
    'patient_check_in' => '患者签到',
    'open_display_screen' => '打开叫号大屏',
    'confirm_check_in' => '确认签到',
    'select_appointment' => '选择今日预约',
    'call_patient' => '叫号',
    'select_chair' => '选择椅位',
    'no_chair' => '不指定椅位',
    'confirm_call' => '确认叫号',
    'call' => '叫号',
    'recall' => '重呼',
    'start_treatment' => '开始就诊',
    'complete' => '完成',
    'confirm_cancel' => '确定要取消该患者的排队吗？',

    // Status
    'status' => [
        'waiting' => '等待中',
        'called' => '已叫号',
        'in_treatment' => '就诊中',
        'completed' => '已完成',
        'cancelled' => '已取消',
        'no_show' => '爽约',
    ],

    // Fields
    'queue_number' => '排队号',
    'check_in_time' => '签到时间',
    'waited_time' => '等待时间',
    'minutes' => '分钟',
    'estimated_wait' => '预计等待',

    // Display screen
    'current_calling' => '当前叫号',
    'please_wait' => '请稍候',
    'no_current_calling' => '暂无叫号',
    'waiting_list' => '候诊队列',
    'no_waiting' => '暂无候诊患者',
    'in_treatment_now' => '正在就诊',
    'display_tip' => '请在听到叫号后前往相应诊室就诊',

    // Tips for display screen
    'tips' => [
        '1' => '请保持手机畅通，叫号后请及时前往就诊',
        '2' => '候诊期间如需离开，请告知前台工作人员',
        '3' => '请配合医护人员做好就诊准备工作',
        '4' => '如有任何问题，请咨询前台工作人员',
    ],

    // Messages
    'check_in_success' => '签到成功',
    'call_success' => '叫号成功',
    'treatment_started' => '就诊已开始',
    'treatment_completed' => '就诊已完成',
    'cancelled' => '已取消排队',
    'invalid_status_for_call' => '当前状态无法叫号',
    'invalid_status_for_start' => '当前状态无法开始就诊',
    'invalid_status_for_complete' => '当前状态无法完成就诊',
    'cannot_cancel' => '当前状态无法取消',
    'no_appointments_today' => '今日暂无可签到的预约',
    'no_waiting_patients' => '暂无等待的患者',

    // Doctor queue
    'my_queue' => '我的候诊',
    'call_next' => '呼叫下一位',
    'current_patient' => '当前患者',
    'waiting_patients' => '等待患者',
];
