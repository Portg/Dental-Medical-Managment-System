/**
 * Requisition Show Page — page script
 * PHP values bridged via window.RequisitionShowConfig (set in Blade).
 */

function _getShowCsrf() {
    var cfg = window.RequisitionShowConfig || {};
    return cfg.csrfToken || '';
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
            data: { _token: _getShowCsrf() },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { setTimeout(function () { location.reload(); }, 1200); }
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
            data: { _token: _getShowCsrf() },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) { setTimeout(function () { location.reload(); }, 1200); }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

function doReject(id) {
    var reason = $('#rejection-reason').val();
    $.LoadingOverlay('show');
    $.ajax({
        type: 'POST',
        url: '/requisitions/' + id + '/reject',
        data: { _token: _getShowCsrf(), rejection_reason: reason },
        success: function (data) {
            $.LoadingOverlay('hide');
            $('#rejectModal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
            if (data.status) { setTimeout(function () { location.reload(); }, 1200); }
        },
        error: function () {
            $.LoadingOverlay('hide');
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
        }
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
            data: { _token: _getShowCsrf() },
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
