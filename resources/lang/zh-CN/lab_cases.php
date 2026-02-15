<?php

return [

    /**
     * Lab Case / 技工单管理 Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于技工单管理模块。
     */

    // ─── 页面标题 ────────────────────────────────────────────────
    'lab_case_management' => '技工单管理',
    'lab_cases'           => '技工单',
    'lab_case'            => '技工单',
    'lab_case_list'       => '技工单列表',
    'lab_case_details'    => '技工单详情',
    'add_lab_case'        => '新建技工单',
    'create_lab_case'     => '创建技工单',
    'edit_lab_case'       => '编辑技工单',
    'view_lab_case'       => '查看技工单',
    'lab_management'      => '技工厂管理',
    'labs'                => '技工厂',
    'lab'                 => '技工厂',
    'lab_list'            => '技工厂列表',
    'add_lab'             => '添加技工厂',
    'edit_lab'            => '编辑技工厂',

    // ─── 技工单表单 ──────────────────────────────────────────────
    'lab_case_no'          => '技工单编号',
    'patient'              => '患者',
    'doctor'               => '医生',
    'select_patient'       => '选择患者',
    'select_doctor'        => '选择医生',
    'select_lab'           => '选择技工厂',
    'prosthesis_type'      => '义齿类型',
    'material'             => '材料',
    'color_shade'          => '比色信息',
    'teeth_positions'      => '牙位',
    'special_requirements' => '特殊工艺要求',
    'notes'                => '备注',
    'appointment'          => '关联预约',
    'medical_case'         => '关联病例',

    // ─── 义齿类型 ────────────────────────────────────────────────
    'type_crown'           => '冠',
    'type_bridge'          => '桥',
    'type_removable'       => '活动义齿',
    'type_implant'         => '种植体',
    'type_veneer'          => '贴面',
    'type_inlay_onlay'     => '嵌体/高嵌体',
    'type_denture'         => '全口义齿',
    'type_orthodontic'     => '正畸器',
    'type_night_guard'     => '夜磨牙垫',
    'type_surgical_guide'  => '种植导板',
    'type_other'           => '其他',

    // ─── 材料 ────────────────────────────────────────────────────
    'material_zirconia'    => '氧化锆',
    'material_pfm'         => '金属烤瓷',
    'material_all_ceramic' => '全瓷',
    'material_emax'        => 'E.max 铸瓷',
    'material_composite'   => '树脂',
    'material_metal'       => '金属',
    'material_acrylic'     => '丙烯酸',
    'material_titanium'    => '钛合金',
    'material_peek'        => 'PEEK',
    'material_other'       => '其他',

    // ─── 状态 ────────────────────────────────────────────────────
    'status'               => '状态',
    'status_pending'       => '待送出',
    'status_sent'          => '已送出',
    'status_in_production' => '制作中',
    'status_returned'      => '已返回',
    'status_try_in'        => '试戴',
    'status_completed'     => '完成',
    'status_rework'        => '返工',
    'update_status'        => '更新状态',

    // ─── 日期与费用 ──────────────────────────────────────────────
    'sent_date'            => '送出日期',
    'expected_return_date' => '预计返回日期',
    'actual_return_date'   => '实际返回日期',
    'lab_fee'              => '加工费',
    'patient_charge'       => '患者收费',
    'profit'               => '利润',
    'overdue'              => '超期',
    'overdue_cases'        => '超期技工单',
    'days_overdue'         => '超期 :days 天',

    // ─── 质量与返工 ──────────────────────────────────────────────
    'quality_rating'       => '质量评分',
    'rework_count'         => '返工次数',
    'rework_reason'        => '返工原因',
    'enter_rework_reason'  => '请输入返工原因',

    // ─── 技工厂表单 ──────────────────────────────────────────────
    'lab_name'             => '技工厂名称',
    'contact'              => '联系人',
    'phone'                => '电话',
    'address'              => '地址',
    'specialties'          => '擅长类型',
    'avg_turnaround_days'  => '平均交付天数',
    'is_active'            => '启用状态',

    // ─── 表格表头 ────────────────────────────────────────────────
    'id'                   => '编号',
    'actions'              => '操作',
    'created_at'           => '创建时间',
    'added_by'             => '录入人',
    'lab_name_header'      => '技工厂',
    'patient_name'         => '患者姓名',
    'doctor_name'          => '医生',

    // ─── 统计 ────────────────────────────────────────────────────
    'statistics'           => '统计',
    'total_cases'          => '总技工单数',
    'active_cases'         => '进行中',
    'completed_cases'      => '已完成',
    'rework_cases'         => '返工',
    'overdue_count'        => '超期数',
    'total_lab_fee'        => '加工费总计',
    'total_patient_charge' => '患者收费总计',
    'total_profit'         => '利润总计',

    // ─── 操作消息 ────────────────────────────────────────────────
    'case_created'         => '技工单创建成功',
    'case_updated'         => '技工单更新成功',
    'case_deleted'         => '技工单删除成功',
    'status_updated'       => '状态更新成功',
    'case_not_found'       => '未找到技工单',
    'error_creating_case'  => '创建技工单时出错',
    'error_updating_case'  => '更新技工单时出错',
    'error_deleting_case'  => '删除技工单时出错',
    'error_updating_status' => '更新状态时出错',
    'lab_created'          => '技工厂添加成功',
    'lab_updated'          => '技工厂更新成功',
    'lab_deleted'          => '技工厂删除成功',
    'lab_not_found'        => '未找到技工厂',
    'error_updating_lab'   => '更新技工厂时出错',
    'lab_has_active_cases' => '该技工厂有进行中的技工单，无法删除',
    'confirm_delete_case'  => '确定要删除此技工单吗？',
    'confirm_delete_lab'   => '确定要删除此技工厂吗？',

    // ─── 打印与导出 ──────────────────────────────────────────────
    'print_lab_case'       => '打印技工单',
    'export_lab_cases'     => '导出技工单',
    'export_excel'         => '导出 Excel',
    'export_pdf'           => '导出 PDF',
    'export_csv'           => '导出 CSV',
    'export_success'       => '导出成功',
    'export_failed'        => '导出失败',
    'exported_by'          => '导出人',
    'exported_at'          => '导出时间',
    'export_filters'       => '导出筛选条件',
    'export_all'           => '导出全部',
    'export_current_page'  => '导出当前页',
    'export_selected'      => '导出选中项',

    // ─── 搜索与筛选 ──────────────────────────────────────────────
    'search_lab_cases'     => '搜索技工单',
    'filter_by_status'     => '按状态筛选',
    'filter_by_lab'        => '按技工厂筛选',
    'filter_by_doctor'     => '按医生筛选',
    'all_statuses'         => '全部状态',
    'all_labs'             => '全部技工厂',
    'all_doctors'          => '全部医生',

    // ─── 面包屑 ──────────────────────────────────────────────────
    'breadcrumb_lab_cases' => '技工单管理',
    'breadcrumb_labs'      => '技工厂管理',

    // ─── 通用 ────────────────────────────────────────────────────
    'save'                 => '保存',
    'cancel'               => '取消',
    'close'                => '关闭',
    'edit'                 => '编辑',
    'delete'               => '删除',
    'view'                 => '查看',
    'print'                => '打印',
    'loading'              => '加载中',
    'processing'           => '处理中...',
    'are_you_sure'         => '您确定吗？',
    'yes_delete_it'        => '是的，删除！',
    'no_records'           => '暂无记录',

];
