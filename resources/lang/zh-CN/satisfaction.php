<?php

return [
    // General
    'title' => '满意度调查',
    'survey_detail' => '调查详情',
    'survey_list' => '调查列表',
    'recent_surveys' => '最近调查',
    'score' => '评分',
    'anonymous' => '匿名用户',

    // Statistics
    'overall_rating' => '总体评分',
    'completed_surveys' => '已完成调查',
    'pending_surveys' => '待填写调查',
    'avg_rating' => '平均评分',
    'reviews' => '条评价',

    // Ratings
    'rating' => '评分',
    'rating_breakdown' => '分项评分',
    'rating_distribution' => '评分分布',
    'ratings' => [
        'overall' => '总体满意度',
        'service' => '服务态度',
        'environment' => '环境设施',
        'wait_time' => '等待时间',
        'doctor' => '医生诊疗',
    ],

    // Status
    'status' => [
        'label' => '状态',
        'pending' => '待填写',
        'completed' => '已完成',
        'expired' => '已过期',
    ],

    // NPS
    'would_recommend' => '推荐意愿 (0-10)',
    'nps_types' => [
        'promoter' => '推荐者',
        'passive' => '被动者',
        'detractor' => '贬损者',
    ],

    // Survey Info
    'survey_info' => '调查信息',
    'channel' => '调查渠道',
    'channels' => [
        'sms' => '短信',
        'wechat' => '微信',
        'app' => 'APP',
        'instore' => '店内',
    ],
    'date' => '填写日期',
    'created_at' => '发送时间',
    'branch' => '门店',
    'patient' => '患者',
    'doctor' => '医生',
    'appointment_date' => '就诊日期',

    // Feedback
    'feedback' => '评价反馈',
    'suggestions' => '改进建议',
    'no_feedback' => '暂无反馈',
    'no_suggestions' => '暂无建议',

    // Actions
    'send_batch' => '批量发送',
    'select_date' => '选择就诊日期',
    'resend' => '重新发送',
    'resend_hint' => '患者尚未填写，可重新发送提醒',
    'resend_not_implemented' => '功能开发中',

    // Messages
    'survey_sent' => '调查问卷已发送',
    'batch_sent' => '已发送 :count 份调查问卷',
    'thank_you' => '感谢您的反馈！',
    'awaiting_response' => '等待患者填写中...',

    // Rankings
    'doctor_ranking' => '医生评分排名',
    'monthly_trend' => '月度趋势',

    // ── 患者侧公开填写页 ────────────────────────────────────────────
    'fill_title'  => '就诊满意度评价',
    'fill_intro'  => '感谢您的到访，请为本次就诊打分，您的反馈将帮助我们改进服务。',
    'nps_help'    => '0 分表示完全不会推荐，10 分表示非常愿意推荐',
    'star_hints'  => [
        1 => '很不满意',
        2 => '不太满意',
        3 => '一般',
        4 => '满意',
        5 => '非常满意',
    ],
    'feedback_placeholder'     => '您对本次就诊的整体感受（选填）',
    'suggestions_placeholder'  => '有哪些地方我们可以做得更好（选填）',
    'submit_anonymously'       => '匿名提交（不显示我的姓名）',
    'submit'                   => '提交评价',
    'submitting'               => '提交中...',
    'overall_rating_required'  => '请先为总体满意度打分',
    'network_error'            => '网络异常，请稍后重试',
    'thank_you_sub'            => '您的评价我们已收到，感谢您的支持。',

    // ── 链接状态 ────────────────────────────────────────────────────
    'link_invalid'      => '链接已失效',
    'link_invalid_hint' => '该评价链接不存在、已填写过或已超过有效期。如需重新填写，请联系诊所前台。',
    'link_expired'      => '评价链接已过期',
    'already_completed' => '该问卷已填写完成，不能重复提交',
    'link_regenerated'  => '已生成新的填写链接，原链接立即失效',

    // ── 后台分发 ────────────────────────────────────────────────────
    'batch_generated'  => '已生成 :count 条填写链接',
    'copy_link'        => '复制填写链接',
    'link_copied'      => '链接已复制，可粘贴到微信发送给患者',
    'copy_failed'      => '复制失败，请手动选中链接复制',
    'fill_link'        => '填写链接',
    'link_expires_at'  => '有效期至 :time',
    'no_link_yet'      => '尚未生成填写链接',
];
