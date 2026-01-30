<?php

return [

    /**
     * Deductions Language Lines (Chinese)
     * --------------------------------------------------------------------------
     * 以下语言行用于工资扣除管理。
     * 您可以根据应用程序的要求自由修改这些语言行。
     */

    // 页面标题
    'deductions' => '扣除项',
    'deduction' => '扣除',
    'salary_deductions' => '工资扣除',
    'deduction_management' => '扣除管理',
    'deduction_list' => '扣除列表',
    'deduction_details' => '扣除详情',
    'add_deduction' => '添加扣除',
    'new_deduction' => '新建扣除',
    'create_deduction' => '创建扣除',
    'edit_deduction' => '编辑扣除',
    'view_deduction' => '查看扣除',

    // 表单标签
    'deduction_name' => '扣除名称',
    'deduction_type' => '扣除类型',
    'employee' => '员工',
    'employee_name' => '员工姓名',
    'employee_id' => '员工编号',
    'amount' => '金额',
    'deduction_amount' => '扣除金额',
    'percentage' => '百分比',
    'effective_date' => '生效日期',
    'start_date' => '开始日期',
    'end_date' => '结束日期',
    'payment_frequency' => '支付频率',
    'description' => '描述',
    'notes' => '备注',
    'remarks' => '备注',
    'reason' => '原因',

    // 扣除类型
    'tax' => '税款',
    'income_tax' => '所得税',
    'social_security' => '社保',
    'health_insurance' => '医疗保险',
    'pension' => '养老金',
    'loan_repayment' => '贷款还款',
    'advance_repayment' => '预支还款',
    'late_attendance' => '迟到',
    'absence' => '缺勤',
    'disciplinary' => '纪律处分',
    'uniform' => '制服',
    'equipment' => '设备',
    'other' => '其他',

    // 支付频率
    'monthly' => '每月',
    'quarterly' => '每季度',
    'yearly' => '每年',
    'one_time' => '一次性',
    'per_payroll' => '每次工资',
    'weekly' => '每周',
    'bi_weekly' => '双周',

    // 计算方法
    'calculation_method' => '计算方法',
    'fixed_amount' => '固定金额',
    'percentage_of_salary' => '工资百分比',
    'hourly_rate' => '小时费率',

    // 状态
    'status' => '状态',
    'active' => '活跃',
    'inactive' => '不活跃',
    'pending' => '待处理',
    'approved' => '已批准',
    'rejected' => '已拒绝',
    'completed' => '已完成',
    'cancelled' => '已取消',

    // 表格表头
    'id' => '编号',
    'employee' => '员工',
    'type' => '类型',
    'date' => '日期',
    'frequency' => '频率',
    'added_by' => '录入人',
    'actions' => '操作',

    // 操作
    'view_details' => '查看详情',
    'approve' => '批准',
    'reject' => '拒绝',
    'delete_deduction' => '删除扣除',
    'activate' => '激活',
    'deactivate' => '停用',
    'cancel' => '取消',

    // 占位符
    'enter_deduction_name' => '输入扣除名称',
    'enter_amount' => '输入金额',
    'enter_description' => '输入描述',
    'enter_reason' => '输入原因',
    'select_employee' => '选择员工',
    'select_deduction_type' => '选择扣除类型',
    'select_frequency' => '选择频率',
    'choose_employee' => '选择员工...',

    // 消息
    'deduction_created_successfully' => '扣除创建成功',
    'deduction_updated_successfully' => '扣除更新成功',
    'deduction_deleted_successfully' => '扣除删除成功',
    'deduction_approved_successfully' => '扣除批准成功',
    'deduction_rejected_successfully' => '扣除拒绝成功',
    'deduction_activated_successfully' => '扣除激活成功',
    'deduction_deactivated_successfully' => '扣除停用成功',
    'deduction_cancelled_successfully' => '扣除取消成功',
    'confirm_delete_deduction' => '您确定要删除此扣除吗？',
    'confirm_approve_deduction' => '您确定要批准此扣除吗？',
    'confirm_reject_deduction' => '您确定要拒绝此扣除吗？',
    'confirm_cancel_deduction' => '您确定要取消此扣除吗？',
    'deduction_not_found' => '未找到扣除',
    'error_creating_deduction' => '创建扣除时出错',
    'error_updating_deduction' => '更新扣除时出错',
    'error_deleting_deduction' => '删除扣除时出错',
    'no_deductions_found' => '未找到扣除',

    // 搜索和筛选
    'search_deductions' => '搜索扣除',
    'filter_deductions' => '筛选扣除',
    'filter_by_employee' => '按员工筛选',
    'filter_by_type' => '按类型筛选',
    'filter_by_status' => '按状态筛选',
    'show_all' => '显示全部',
    'show_active' => '显示活跃',
    'show_inactive' => '显示不活跃',

    // 报表和统计
    'total_deductions' => '总扣除数',
    'total_amount' => '总金额',
    'deductions_by_type' => '按类型分类扣除',
    'monthly_deductions' => '月度扣除',
    'deductions_report' => '扣除报表',

    // 验证
    'amount_required' => '金额为必填项',
    'employee_required' => '员工为必填项',
    'type_required' => '扣除类型为必填项',
    'invalid_amount' => '无效的金额',
    'amount_must_be_positive' => '金额必须为正数',
    'reason_required' => '原因为必填项',

    // 附加
    'statutory' => '法定',
    'voluntary' => '自愿',
    'is_statutory' => '是否法定',
    'deduction_category' => '扣除类别',
    'include_in_payroll' => '包含在工资单中',
    'installments' => '分期付款',
    'number_of_installments' => '分期次数',
    'installment_amount' => '分期金额',
    'remaining_installments' => '剩余分期',

    // 工资单选择的具体扣除类型
    'loan' => '贷款',

];
