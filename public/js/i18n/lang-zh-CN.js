/**
 * Chinese (Simplified) Translations for JavaScript
 * 中文（简体）JavaScript翻译文件
 *
 * This file contains Chinese translations for JavaScript messages,
 * alerts, and DataTables configurations used throughout the application.
 */

// Main translation object
var lang_zh_CN = {
    // Common Actions
    'add': '添加',
    'edit': '编辑',
    'delete': '删除',
    'save': '保存',
    'cancel': '取消',
    'close': '关闭',
    'submit': '提交',
    'confirm': '确认',
    'yes': '是',
    'no': '否',
    'ok': '确定',
    'view': '查看',
    'print': '打印',
    'export': '导出',
    'search': '搜索',
    'filter': '筛选',
    'refresh': '刷新',
    'back': '返回',

    // Status Messages
    'success': '成功',
    'error': '错误',
    'warning': '警告',
    'info': '信息',
    'loading': '加载中...',
    'processing': '处理中...',
    'please_wait': '请稍候...',

    // Confirmation Messages
    'are_you_sure': '您确定吗？',
    'confirm_delete': '您确定要删除吗？',
    'confirm_action': '您确定要执行此操作吗？',
    'cannot_be_undone': '此操作无法撤销！',
    'delete_confirmation': '删除确认',

    // Success Messages
    'operation_successful': '操作成功！',
    'saved_successfully': '保存成功！',
    'updated_successfully': '更新成功！',
    'deleted_successfully': '删除成功！',
    'added_successfully': '添加成功！',
    'sent_successfully': '发送成功！',

    // Error Messages
    'operation_failed': '操作失败！',
    'error_occurred': '发生错误，请重试。',
    'something_went_wrong': '出错了，请重试。',
    'please_try_again': '请重试。',
    'invalid_input': '输入无效。',
    'required_field': '此字段为必填项。',
    'please_select': '请选择。',
    'no_data_available': '没有可用数据。',

    // Form Validation
    'field_required': '此字段为必填项',
    'invalid_email': '无效的邮箱地址',
    'invalid_phone': '无效的电话号码',
    'invalid_number': '请输入有效的数字',
    'min_length': '最小长度',
    'max_length': '最大长度',
    'must_match': '必须匹配',

    // Table Messages
    'no_records_found': '未找到记录',
    'showing': '显示',
    'of': '共',
    'entries': '条记录',
    'loading_data': '加载数据中...',

    // Patient Related
    'patient': '患者',
    'patient_name': '患者姓名',
    'patient_id': '患者编号',
    'select_patient': '选择患者',
    'patient_required': '请选择患者',

    // Appointment Related
    'appointment': '预约',
    'appointment_date': '预约日期',
    'appointment_time': '预约时间',
    'select_date': '选择日期',
    'select_time': '选择时间',
    'select_doctor': '选择医生',
    'doctor_required': '请选择医生',

    // Invoice Related
    'invoice': '发票',
    'add_item': '添加项目',
    'remove_item': '删除项目',
    'quantity': '数量',
    'unit_price': '单价',
    'amount': '金额',
    'subtotal': '小计',
    'discount': '折扣',
    'tax': '税费',
    'total': '总计',
    'at_least_one_item': '请至少添加一个项目',

    // Medical Records
    'allergy': '过敏',
    'chronic_disease': '慢性病',
    'surgery': '手术',
    'prescription': '处方',
    'medication': '药物',
    'dosage': '剂量',
    'frequency': '频率',
    'duration': '疗程',

    // File Upload
    'upload': '上传',
    'choose_file': '选择文件',
    'file_selected': '已选择文件',
    'max_file_size': '最大文件大小',
    'allowed_types': '允许的文件类型',
    'uploading': '上传中...',
    'upload_success': '上传成功！',
    'upload_failed': '上传失败',

    // Date & Time
    'today': '今天',
    'yesterday': '昨天',
    'tomorrow': '明天',
    'this_week': '本周',
    'this_month': '本月',
    'select_date_range': '选择日期范围',
    'from': '从',

    // Miscellaneous
    'optional': '可选',
    'required': '必填',
    'select': '选择',
    'none': '无',
    'all': '全部',
    'actions': '操作',
    'details': '详情',
    'notes': '备注',
    'description': '描述',
    'status': '状态',
    'date': '日期',
    'time': '时间'
};

// DataTables Chinese Translation
var dataTablesLang_zh_CN = {
    "metronicGroupActions" : "_TOTAL_ 条记录已选中:  ",
    "metronicAjaxRequestGeneralError" : "请求数据时出错，请重试。",

    // data tables spesific
    "lengthMenu": "<span class='seperator'>|</span>显示 _MENU_ 条记录",
    "info": "<span class='seperator'>|</span>共 _TOTAL_ 条",
    "infoEmpty": "显示第 0 至 0 条记录，共 0 条",
    "emptyTable": "表中没有数据",
    "zeroRecords": "未找到匹配记录",
    "infoFiltered": "(从 _MAX_ 条记录中过滤)",
    "search": "搜索: ",
    "processing": "<span class='loading-spinner margin-right-5'></span>处理中...",
    "paginate": {
        "previous": "上一页",
        "next": "下一页",
        "last": "末页",
        "first": "首页",
        "page": "页",
        "pageOf": "共"
    }
};

// SweetAlert Button Text
var sweetAlertLang_zh_CN = {
    confirm: '确定',
    cancel: '取消',
    ok: '确定',
    yes: '是',
    no: '否',
    delete: '删除'
};

// Helper function to get translation
function trans(key, defaultValue) {
    return lang_zh_CN[key] || defaultValue || key;
}

// Helper function for confirmation dialogs
function confirmAction(message, callback) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: trans('are_you_sure', '您确定吗？'),
            text: message || trans('confirm_action', '您确定要执行此操作吗？'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: sweetAlertLang_zh_CN.yes,
            cancelButtonText: sweetAlertLang_zh_CN.cancel
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    } else if (confirm(message || trans('confirm_action', '您确定要执行此操作吗？'))) {
        if (typeof callback === 'function') {
            callback();
        }
    }
}

// Helper function for delete confirmation
function confirmDelete(callback, message) {
    var msg = message || trans('confirm_delete', '您确定要删除吗？') + '\n' +
        trans('cannot_be_undone', '此操作无法撤销！');
    confirmAction(msg, callback);
}

// Helper function for success messages
function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: trans('success', '成功'),
            text: message || trans('operation_successful', '操作成功！'),
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        alert(message || trans('operation_successful', '操作成功！'));
    }
}

// Helper function for error messages
function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: trans('error', '错误'),
            text: message || trans('error_occurred', '发生错误，请重试。')
        });
    } else {
        alert(message || trans('error_occurred', '发生错误，请重试。'));
    }
}

// Helper function for warning messages
function showWarning(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: trans('warning', '警告'),
            text: message
        });
    } else {
        alert(message);
    }
}

// Helper function for info messages
function showInfo(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: trans('info', '信息'),
            text: message
        });
    } else {
        alert(message);
    }
}

// Initialize DataTables with Chinese language when document is ready
$(document).ready(function() {
    // Set default DataTables language
    if ($.fn.DataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            language: dataTablesLang_zh_CN
        });
    }
});