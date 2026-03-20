$(function () {
    dataTable = $('#self-accounts-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.SelfAccountsConfig.baseUrl,
            data: function (d) {
                d.search = $('input[type="search"]').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
            {data: 'account_no', name: 'account_no', visible: false},
            {data: 'account_holder', name: 'account_holder'},
            {data: 'holder_phone_no', name: 'holder_phone_no'},
            {data: 'holder_email', name: 'holder_email'},
            {data: 'account_balance', name: 'account_balance'},
            {data: 'addedBy', name: 'addedBy'},
            {data: 'status', name: 'status'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function createRecord() {
    $("#company-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $('#company-modal').modal('show');
}

function save_data() {
    var id = $('#id').val();
    if (id === "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#company-form').serialize(),
        url: window.SelfAccountsConfig.baseUrl,
        success: function (data) {
            $('#company-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_changes'));
            $('#company-modal').modal('show');
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#company-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: window.SelfAccountsConfig.baseUrl + '/' + id + '/edit',
        success: function (data) {
            $('#id').val(id);
            $('[name="name"]').val(data.account_holder);
            $('[name="email"]').val(data.holder_email);
            $('[name="phone_no"]').val(data.holder_phone_no);
            $('[name="address"]').val(data.holder_address);
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#company-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#company-form').serialize(),
        url: window.SelfAccountsConfig.baseUrl + '/' + $('#id').val(),
        success: function (data) {
            $('#company-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $.LoadingOverlay("hide");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_changes'));
            $('#company-modal').modal('show');
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('self_accounts.confirm_delete_account'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            closeOnConfirm: false
        },
        function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: {
                    _token: CSRF_TOKEN
                },
                url: window.SelfAccountsConfig.baseUrl + '/' + id,
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        });
}
