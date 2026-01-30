<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reports Language Lines
    |--------------------------------------------------------------------------
    |
    | Language lines for various reports and analytics
    |
    */

    // General
    'reports' => '报表',
    'report' => '报表',
    'generate_report' => '生成报表',
    'view_report' => '查看报表',
    'print_report' => '打印报表',
    'export_report' => '导出报表',
    'download_report' => '下载报表',
    'report_generated' => '报表已生成',
    'no_data_available' => '没有可用数据',

    // Report Types
    'daily_cash' => '每日现金',
    'daily_cash_report' => '每日现金报表',
    'daily_expenses' => '每日费用',
    'daily_expenses_report' => '每日费用报表',
    'daily_insurance' => '每日保险',
    'daily_insurance_report' => '每日保险报表',
    'debtors_report' => '债务人报表',
    'doctor_performance_report' => '医生绩效报表',
    'procedures_income_report' => '项目收入报表',
    'invoice_payments_report' => '发票支付报表',
    'invoicing_report' => '发票报表',
    'insurance_report' => '保险报表',
    'budget_line_report' => '预算明细报表',
    'preview_budget_line' => '预览预算明细',
    'patient_report' => '患者报表',
    'appointment_report' => '预约报表',
    'financial_report' => '财务报表',
    'income_report' => '收入报表',
    'expense_report' => '费用报表',
    'profit_loss_report' => '损益报表',
    'sales_report' => '销售报表',
    'inventory_report' => '库存报表',
    'pharmacy_report' => '药房报表',

    // Date Ranges
    'date_range' => '日期范围',
    'from_date' => '开始日期',
    'to_date' => '结束日期',
    'select_date' => '选择日期',
    'start_date' => '开始日期',
    'end_date' => '结束日期',
    'select_date_range' => '选择日期范围',
    'today' => '今天',
    'yesterday' => '昨天',
    'this_week' => '本周',
    'last_week' => '上周',
    'this_month' => '本月',
    'last_month' => '上月',
    'this_year' => '今年',
    'last_year' => '去年',
    'custom_range' => '自定义范围',

    // Filters
    'filter_by' => '筛选',
    'filter_by_date' => '按日期筛选',
    'filter_by_doctor' => '按医生筛选',
    'filter_by_patient' => '按患者筛选',
    'filter_by_branch' => '按分支筛选',
    'filter_by_category' => '按分类筛选',
    'filter_by_status' => '按状态筛选',
    'select_doctor' => '选择医生',
    'select_patient' => '选择患者',
    'select_branch' => '选择分支',
    'all_doctors' => '所有医生',
    'all_patients' => '所有患者',
    'all_branches' => '所有分支',

    // Daily Cash Report
    'cash_received' => '现金收入',
    'card_payments' => '刷卡支付',
    'mobile_payments' => '移动支付',
    'bank_transfers' => '银行转账',
    'insurance_payments' => '保险支付',
    'total_cash' => '总现金',
    'opening_balance' => '期初余额',
    'closing_balance' => '期末余额',
    'cash_on_hand' => '手头现金',

    // Daily Expenses Report
    'expenses_by_category' => '按分类费用',
    'expense_breakdown' => '费用明细',

    // Daily Insurance Report
    'total_claims' => '总理赔',
    'approved_claims' => '已批准理赔',
    'pending_claims' => '待处理理赔',
    'rejected_claims' => '已拒绝理赔',
    'insurance_income' => '保险收入',

    // Debtors Report
    'debtors' => '债务人',
    'patient_name' => '患者姓名',
    'invoice_number' => '发票号',
    'invoice_date' => '发票日期',
    'due_date' => '到期日期',
    'total_amount' => '总金额',
    'amount_paid' => '已付金额',
    'balance_due' => '应付余额',
    'days_overdue' => '逾期天数',
    'total_outstanding' => '未付总额',

    // Doctor Performance Report
    'doctor_name' => '医生姓名',
    'total_appointments' => '总预约数',
    'completed_appointments' => '已完成预约',
    'cancelled_appointments' => '已取消预约',
    'total_patients' => '总患者数',
    'new_patients' => '新患者',
    'revenue_generated' => '创造收入',
    'procedures_performed' => '完成项目',
    'performance_metrics' => '绩效指标',

    // Procedures Income Report
    'procedure_name' => '项目名称',
    'procedure_code' => '项目代码',
    'number_of_procedures' => '项目数量',
    'total_revenue' => '总收入',
    'average_price' => '平均价格',
    'procedures_by_type' => '按类型项目',
    'top_procedures' => '热门项目',

    // Patient Report
    'total_visits' => '总就诊次数',
    'last_visit' => '最后就诊',
    'next_appointment' => '下次预约',
    'patient_status' => '患者状态',
    'active_patients' => '活跃患者',
    'inactive_patients' => '不活跃患者',
    'new_patient_registrations' => '新患者登记',

    // Appointment Report
    'total_scheduled' => '总预约数',
    'appointments_today' => '今日预约',
    'upcoming_appointments' => '即将到来的预约',
    'past_appointments' => '过去的预约',
    'appointment_status_breakdown' => '预约状态明细',
    'confirmed' => '已确认',
    'pending' => '待处理',
    'cancelled' => '已取消',
    'no_show' => '未到',
    'appointment_types' => '预约类型',

    // Financial Report
    'total_income' => '总收入',
    'net_income' => '净收入',
    'gross_income' => '毛收入',
    'profit' => '利润',
    'loss' => '亏损',
    'profit_margin' => '利润率',
    'income_vs_expenses' => '收入vs费用',
    'monthly_comparison' => '月度对比',
    'yearly_comparison' => '年度对比',

    // Income Report
    'income_by_source' => '按来源收入',
    'consultations' => '诊疗',
    'procedures' => '项目',
    'pharmacy' => '药房',
    'lab_tests' => '检验',
    'other_services' => '其他服务',
    'payment_method_breakdown' => '付款方式明细',

    // Summary & Statistics
    'summary' => '摘要',
    'statistics' => '统计',
    'total' => '总计',
    'average' => '平均',
    'count' => '数量',
    'percentage' => '百分比',
    'growth' => '增长',
    'comparison' => '对比',
    'trend' => '趋势',

    // Charts & Graphs
    'chart' => '图表',
    'graph' => '图形',
    'pie_chart' => '饼图',
    'bar_chart' => '柱状图',
    'line_chart' => '折线图',
    'view_chart' => '查看图表',
    'view_table' => '查看表格',

    // Export Options
    'export_to_pdf' => '导出为PDF',
    'export_to_excel' => '导出为Excel',
    'export_to_csv' => '导出为CSV',
    'print' => '打印',

    // Messages
    'report_generated_successfully' => '报表生成成功！',
    'no_data_for_selected_period' => '所选期间没有数据。',
    'please_select_date_range' => '请选择日期范围。',
    'select_date_range' => '请选择日期范围',
    'invalid_date_range' => '无效的日期范围。',
    'start_date_must_be_before_end_date' => '开始日期必须早于结束日期。',

    // Period Labels
    'daily' => '每日',
    'weekly' => '每周',
    'monthly' => '每月',
    'quarterly' => '每季度',
    'yearly' => '每年',
    'period' => '期间',

    // Other
    'generated_on' => '生成于',
    'generated_by' => '生成者',
    'report_date' => '报表日期',
    'page' => '页',
    'of' => '共',

    // Table Headers - General
    'patient_name' => '患者姓名',
    'invoice_no' => '发票号',
    'invoice_number' => '发票号码',
    'invoice_date' => '发票日期',
    'payment_date' => '支付日期',
    'payment_method' => '付款方式',
    'added_by' => '添加者',
    'surname' => '姓',
    'first_name' => '名',
    'last_name' => '姓',
    'othername' => '其他名字',
    'phone_no' => '电话号码',

    // Report Specific Fields
    'todays_cash_report' => '今日现金报表',
    'todays_expense_report' => '今日费用报表',
    'todays_insurance_report' => '今日保险报表',
    'receivables_report' => '应收款报表',

    // Daily Cash
    'invoice_amount' => '发票金额',
    'paid_amount' => '已付金额',

    // Debtors Report
    'outstanding_balance' => '未付余额',
    'amount_paid' => '已付金额',

    // Doctor Performance
    'choose_doctor' => '选择医生',
    'procedures_cost' => '项目费用',
    'overall_invoice_amount' => '发票总金额',
    'outstanding_amount' => '未付金额',

    // Procedures Income Report
    'procedure' => '项目',
    'procedure_sales' => '项目销售额',
    'procedure_income' => '项目收入',

    // Budget Line Report
    'budget_lines' => '预算明细',
    'total_items' => '总项数',

    // Filters
    'period' => '期间',
    'filter_report' => '筛选报表',
    'filter_invoices' => '筛选发票',
    'all' => '全部',
    'insurance' => '保险',
    'credit' => '信用',
    'cash' => '现金',

    // Buttons
    'download_excel_report' => '下载Excel报表',

    // Dashboard Charts (SuperAdmin)
    'today_appointments' => '今日预约',
    'today_cash_amount' => '今日现金收入',
    'today_insurance_amount' => '今日保险收入',
    'today_expenses_amount' => '今日支出',
    'monthly_income_chart_cash_in' => '月度现金收入图表',
    'monthly_expenses_chart_cash_out' => '月度现金支出图表',
    'monthly_overall_income_chart' => '月度总收入图表',
    'monthly_overall_chart_income_expenditure' => '月度收支总览图表',
    'daily_cash_payments' => '每日现金支付',
    'daily_insurance_payments' => '每日保险支付',
    'over_roll' => '累计滚动',
    'income' => '收入',
    'expenditure' => '支出',

    // Patient Source Analysis Report
    'patient_source_analysis' => '患者来源分析',
    'total_new_patients' => '新增患者总数',
    'source_channels' => '来源渠道',
    'active_channels' => '活跃渠道',
    'top_source' => '主要来源',
    'of_total' => '占比',
    'avg_conversion' => '平均转化率',
    'appointment_conversion' => '预约转化',
    'source_distribution' => '来源分布',
    'source_pie_chart' => '来源占比图',
    'source_details' => '来源明细表',
    'source_name' => '来源名称',
    'patient_count' => '患者数量',
    'converted_count' => '已转化数',
    'conversion_rate' => '转化率',
    'conversion' => '转化',
    'patients' => '患者',

    // Revisit Rate Report
    'revisit_rate_analysis' => '复诊率分析',
    'total_visit_patients' => '就诊患者总数',
    'first_visit_patients' => '初诊患者',
    'revisit_patients' => '复诊患者',
    'revisit_rate' => '复诊率',
    'monthly_revisit_trend' => '月度复诊趋势',
    'revisit_interval' => '复诊间隔分布',
    'doctor_revisit_ranking' => '医生复诊排名',
    'appointments' => '次预约',
    'avg_visits' => '次/人',
    'lost_patient_alert' => '流失患者预警',
    'no_visit_90_days' => '超过90天未复诊',
    'last_visit' => '最后就诊',
    'no_lost_patients' => '暂无流失预警患者',

    // Revisit Interval Labels
    'interval_within_7_days' => '7天内',
    'interval_8_14_days' => '8-14天',
    'interval_15_30_days' => '15-30天',
    'interval_31_60_days' => '31-60天',
    'interval_61_90_days' => '61-90天',
    'interval_over_90_days' => '90天以上',

];