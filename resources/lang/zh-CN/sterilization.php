<?php

return [
    'records_tab'     => '灭菌记录',
    'kits_tab'        => '器械包管理',
    'records_export'  => '导出记录',
    'kits_desc'       => '维护器械包编号、名称、器械数量与启停状态。',
    'filter_kit'      => '器械包',
    'filter_status'   => '状态',
    'record_add_title' => '新增灭菌记录',
    'record_edit_title' => '编辑灭菌记录',
    'kit_add_title'    => '新增器械包',
    'kit_edit_title'   => '编辑器械包',

    // 器械包字段
    'kit_no'          => '包号',
    'kit_name'        => '包名称',
    'instruments'     => '器械清单',
    'instrument_name' => '器械名称',
    'quantity'        => '数量',

    // 灭菌记录字段
    'batch_no'         => '批次号',
    'method'           => '灭菌方式',
    'method_autoclave' => '高压蒸汽',
    'method_chemical'  => '化学消毒',
    'method_dry_heat'  => '干热灭菌',
    'temperature'      => '温度(℃)',
    'duration_minutes' => '时长(分钟)',
    'operator'         => '操作员',
    'sterilized_at'    => '灭菌时间',
    'expires_at'       => '有效期至',

    // 状态
    'status_valid'    => '有效',
    'status_used'     => '已使用',
    'status_expired'  => '已过期',
    'status_expiring' => '即将过期',
    'status_voided'   => '已作废',

    // 使用登记
    'log_use'     => '登记使用',
    'used_at'     => '使用时间',
    'used_by'     => '操作医生',
    'usage_notes' => '备注',
    'revoke_use'  => '撤销使用',

    // 成功/错误消息
    'record_created_successfully' => '灭菌记录创建成功',
    'record_updated_successfully' => '灭菌记录更新成功',
    'record_deleted_successfully' => '灭菌记录删除成功',
    'kit_created_successfully'    => '器械包创建成功',
    'kit_updated_successfully'    => '器械包更新成功',
    'kit_deleted_successfully'    => '器械包删除成功',
    'usage_recorded_successfully' => '使用记录登记成功',
    'usage_revoked_successfully'  => '使用记录已撤销',
];
