/**
 * Member Show Page — page script
 * PHP values bridged via window.MemberShowConfig (set in Blade).
 */

$(document).ready(function () {
    loadTransactions();
    loadSharedHolders();
    loadAuditLogs();
});

function loadSharedHolders() {
    var cfg = window.MemberShowConfig || {};
    $('#shared_holders_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/members/' + cfg.memberId + '/shared-holders',
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',   orderable: false, searchable: false },
            { data: 'patient_name',  name: 'patient_name' },
            { data: 'relationship',  name: 'relationship' },
            { data: 'removeBtn',     name: 'removeBtn',     orderable: false, searchable: false }
        ],
        language: LanguageManager.getDataTableLang(),
        paging: false,
        info: false
    });
}

function loadAuditLogs() {
    var cfg = window.MemberShowConfig || {};
    $('#audit_logs_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/members/' + cfg.memberId + '/audit-logs',
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false },
            { data: 'actionBadge',    name: 'actionBadge' },
            { data: 'field_name',     name: 'field_name' },
            { data: 'old_value',      name: 'old_value' },
            { data: 'new_value',      name: 'new_value' },
            { data: 'operator_name',  name: 'operator_name' },
            { data: 'created_at',     name: 'created_at' }
        ],
        order: [[6, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

function showAddSharedHolder() {
    var cfg = window.MemberShowConfig || {};
    swal({
        title: LanguageManager.trans('members.add_shared_holder'),
        text: LanguageManager.trans('members.shared_patient'),
        type: 'input',
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('common.save'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        inputPlaceholder: LanguageManager.trans('members.shared_patient') + ' ID',
        closeOnConfirm: false
    }, function (inputValue) {
        if (inputValue === false) return;
        var patientId = parseInt(inputValue);
        if (isNaN(patientId) || patientId <= 0) {
            swal.showInputError(LanguageManager.trans('members.shared_patient'));
            return false;
        }
        $.ajax({
            url: '/members/' + cfg.memberId + '/shared-holders',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                shared_patient_id: patientId,
                relationship: 'other'
            },
            success: function (response) {
                if (response.status) {
                    swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                    $('#shared_holders_table').DataTable().ajax.reload();
                } else {
                    swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                }
            }
        });
    });
}

function removeSharedHolder(id) {
    swal({
        title: LanguageManager.trans('messages.confirm_delete'),
        text: LanguageManager.trans('members.confirm_remove_shared'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function (isConfirm) {
        if (isConfirm) {
            $.ajax({
                url: '/members/shared-holders/' + id,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    if (response.status) {
                        swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                        $('#shared_holders_table').DataTable().ajax.reload();
                    } else {
                        swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                    }
                }
            });
        }
    });
}

function showSetPassword(patientId) {
    swal({
        title: LanguageManager.trans('members.set_password'),
        text: LanguageManager.trans('members.new_password'),
        type: 'input',
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('common.save'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        inputType: 'password',
        closeOnConfirm: false
    }, function (inputValue) {
        if (inputValue === false) return;
        if (!inputValue || inputValue.length < 4) {
            swal.showInputError(LanguageManager.trans('members.password_too_short'));
            return false;
        }
        $.ajax({
            url: '/members/' + patientId + '/password',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                password: inputValue,
                password_confirmation: inputValue
            },
            success: function (response) {
                if (response.status) {
                    swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                } else {
                    swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                }
            },
            error: function (xhr) {
                swal({ title: LanguageManager.trans('messages.error'), text: xhr.responseJSON ? xhr.responseJSON.message : 'Error', type: 'error' });
            }
        });
    });
}
