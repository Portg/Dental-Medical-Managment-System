<?php

return [
    // 面板
    'title'              => '剩余项目',
    'subtitle'           => '已收费、还没做完的项目',
    'empty'              => '该患者没有未做完的预收项目',
    'loading'            => '正在读取剩余项目…',

    // 表头
    'service'            => '项目',
    'invoice_no'         => '账单号',
    'invoice_date'       => '收费日期',
    'tooth_no'           => '牙位',
    'total_qty'          => '已购',
    'used_qty'           => '已用',
    'remaining_qty'      => '剩余',
    'remaining_value'    => '剩余价值',
    'prepaid_value'      => '预收未兑现',
    'arrears'            => '该行欠费',
    'action'             => '操作',

    // 汇总
    'summary_items'      => '未做完项目',
    'summary_qty'        => '剩余次数',
    'summary_value'      => '剩余价值',
    'summary_prepaid'    => '预收未兑现',
    'summary_hint'       => '「预收未兑现」是钱已经收到、服务还欠着的部分。',

    // 核销
    'consume'            => '用一次',
    'consume_n'          => '核销',
    'consume_title'      => '核销预收项目',
    'consume_qty'        => '本次核销数量',
    'consume_notes'      => '备注',
    'consume_submit'     => '确认核销',
    'consume_success'    => '已核销',
    'revoke'             => '撤销',
    'revoke_success'     => '已撤销这次核销',
    'revoke_confirm'     => '撤销这次核销？余量会加回去。',

    // 流水
    'history'            => '核销记录',
    'history_empty'      => '还没有核销记录',
    'history_date'       => '日期',
    'history_qty'        => '数量',
    'history_doctor'     => '操作医生',
    'history_notes'      => '备注',

    // 错误
    'qty_must_be_positive' => '核销数量必须大于 0',
    'item_not_trackable'   => '这笔收费不是按次核销的项目',
    'exceeds_remaining'    => '超过剩余数量，最多还能核销 :remaining',
    'usage_not_found'      => '找不到这条核销记录',

    // 项目维护
    'track_delivery'       => '按次核销',
    'track_delivery_hint'  => '勾上后，这个项目收费后会进「剩余项目」，做一次核销一次。适合次卡、疗程、正畸全程这类先收钱后分次做的项目。',
];
