/**
 * 申领单列表页 JS
 * 遵循：Blade 无内联 script，JS 放 public/include_js/
 */
var dataTable;
var csrfToken = $('meta[name="csrf-token"]').attr('content');

function loadTable() {
    dataTable = $('#requisitions-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: '/requisitions',
            data: function (d) {
                d.status = $('#filter-status').val();
            }
        },
        dom: 'rtip',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'stock_out_no', name: 'stock_out_no' },
            { data: 'stock_out_date', name: 'stock_out_date' },
            { data: 'items_preview', name: 'items_preview', orderable: false, searchable: false },
            { data: 'total_qty', name: 'total_qty', orderable: false, searchable: false },
            { data: 'added_by_name', name: 'added_by_name', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    setupEmptyStateHandler();
}

function filterTable() {
    dataTable.ajax.reload();
}

function clearFilters() {
    $('#filter-status').val('');
    dataTable.ajax.reload();
}

function submitRequisition(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('inventory.submit_for_approval'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-warning',
        confirmButtonText: LanguageManager.trans('common.confirm'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'POST',
            url: '/requisitions/' + id + '/submit',
            data: { _token: csrfToken },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { dataTable.ajax.reload(); }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

function approveRequisition(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('inventory.approve'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-success',
        confirmButtonText: LanguageManager.trans('common.confirm'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'POST',
            url: '/requisitions/' + id + '/approve',
            data: { _token: csrfToken },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { dataTable.ajax.reload(); }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

function rejectRequisition(id) {
    swal({
        title: LanguageManager.trans('inventory.reject'),
        text: LanguageManager.trans('inventory.rejection_reason'),
        type: 'input',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('inventory.reject'),
        closeOnConfirm: false
    }, function (inputVal) {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'POST',
            url: '/requisitions/' + id + '/reject',
            data: { _token: csrfToken, rejection_reason: inputVal },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { dataTable.ajax.reload(); }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

function cloneRequisition(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('inventory.reapply'),
        type: 'info',
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('common.confirm'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'POST',
            url: '/requisitions/' + id + '/clone',
            data: { _token: csrfToken },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status && data.redirect) {
                    setTimeout(function () { window.location.href = data.redirect; }, 1200);
                }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

function deleteRequisition(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('common.delete_confirm'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'DELETE',
            url: '/requisitions/' + id,
            data: { _token: csrfToken },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { dataTable.ajax.reload(); }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}
