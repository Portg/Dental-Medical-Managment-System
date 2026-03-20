$(function () {
    dataTable = $('#chairs-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.ChairsConfig.baseUrl,
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'chair_code', name: 'chair_code'},
            {data: 'chair_name', name: 'chair_name'},
            {data: 'branch', name: 'branch'},
            {data: 'statusLabel', name: 'statusLabel'},
            {data: 'addedBy', name: 'addedBy'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function createRecord() {
    $("#chair-form")[0].reset();
    $('#chair_id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $('.alert-danger').hide();
    $('#chair-modal').modal('show');
}

function save_data() {
    var id = $('#chair_id').val();
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
    $('.alert-danger').hide().find('ul').empty();

    $.ajax({
        type: 'POST',
        data: $('#chair-form').serialize(),
        url: window.ChairsConfig.baseUrl,
        success: function (data) {
            $('#chair-modal').modal('hide');
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
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').find('ul').append('<li>' + value + '</li>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#chair-form")[0].reset();
    $('#chair_id').val('');
    $('#btn-save').attr('disabled', false);
    $('.alert-danger').hide();

    $.ajax({
        type: 'get',
        url: window.ChairsConfig.baseUrl + '/' + id + '/edit',
        success: function (data) {
            $('#chair_id').val(id);
            $('[name="chair_code"]').val(data.chair_code);
            $('[name="chair_name"]').val(data.chair_name);
            $('[name="status"]').val(data.status);
            $('[name="branch_id"]').val(data.branch_id);
            $('[name="notes"]').val(data.notes);
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#chair-modal').modal('show');
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
    $('.alert-danger').hide().find('ul').empty();

    $.ajax({
        type: 'PUT',
        data: $('#chair-form').serialize(),
        url: window.ChairsConfig.baseUrl + '/' + $('#chair_id').val(),
        success: function (data) {
            $('#chair-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').find('ul').append('<li>' + value + '</li>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('chairs.delete_confirm_message'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            closeOnConfirm: false,
            cancelButtonText: LanguageManager.trans('common.cancel')
        },
        function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: window.ChairsConfig.baseUrl + '/' + id,
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
