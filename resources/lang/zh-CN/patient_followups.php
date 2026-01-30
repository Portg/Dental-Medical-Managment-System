<?php

return [
    // Page titles
    'page_title' => '患者随访',
    'all_followups' => '全部随访',
    'add_followup' => '添加随访',
    'edit_followup' => '编辑随访',
    'view_followup' => '查看随访',
    'complete_followup' => '完成随访',

    // Field labels
    'followup_no' => '随访编号',
    'patient' => '患者',
    'type' => '随访方式',
    'scheduled_date' => '计划日期',
    'completed_date' => '完成日期',
    'status' => '状态',
    'purpose' => '随访目的',
    'notes' => '备注',
    'outcome' => '随访结果',
    'next_followup_date' => '下次随访日期',
    'added_by' => '录入人',

    // Follow-up types
    'type_phone' => '电话',
    'type_sms' => '短信',
    'type_email' => '邮件',
    'type_visit' => '回访',
    'type_other' => '其他',

    // Status values
    'status_pending' => '待处理',
    'status_completed' => '已完成',
    'status_cancelled' => '已取消',
    'status_no_response' => '无应答',

    // Flags
    'overdue' => '已逾期',

    // Placeholders
    'purpose_placeholder' => '例如：术后复查、预约提醒',
    'notes_placeholder' => '请输入备注或说明',
    'outcome_placeholder' => '请输入随访结果',

    // Actions
    'mark_complete' => '标记完成',

    // Messages
    'followup_created_successfully' => '随访记录创建成功',
    'followup_updated_successfully' => '随访记录更新成功',
    'followup_completed_successfully' => '随访已标记为完成',
    'followup_deleted_successfully' => '随访记录删除成功',
    'delete_confirmation' => '确定要删除此随访记录吗？',
    'complete_confirmation' => '此操作将把随访标记为今天完成。',
    'no_followups_found' => '暂无随访记录',
];
