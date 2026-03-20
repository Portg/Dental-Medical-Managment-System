$(function () {
    dataTable = $('#holidays_table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.HolidaysConfig.baseUrl,
            data: function (d) {
                d.filter_name = $('#filter_name').val();
                d.filter_repeat = $('#filter_repeat').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true, width: '50px'},
            {data: 'created_at', name: 'created_at', width: '160px'},
            {data: 'name', name: 'name'},
            {data: 'holiday_date', name: 'holiday_date', width: '120px'},
            {data: 'repeat_date', name: 'repeat_date', width: '100px'},
            {data: 'addedBy', name: 'addedBy', width: '100px'},
            {data: 'action', name: 'action', orderable: false, searchable: false, width: '90px'}
        ]
    });

    setupEmptyStateHandler();
});

function doSearch() {
    dataTable.draw(true);
}

function clearFilters() {
    $('#filter_name').val('');
    $('#filter_repeat').val('');
    dataTable.draw(true);
}

function createRecord() {
    $("#holidays-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#holidays-modal').modal('show');
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
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'POST',
        data: $('#holidays-form').serialize(),
        url: window.HolidaysConfig.baseUrl,
        success: function (data) {
            $('#holidays-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
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
    $("#holidays-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);

    $.ajax({
        type: 'get',
        url: window.HolidaysConfig.baseUrl + '/' + id + '/edit',
        success: function (data) {
            $('#id').val(id);
            $('[name="name"]').val(data.name);
            $('[name="holiday_date"]').val(data.holiday_date);
            $('input[name^="repeat_date"][value="' + data.repeat_date + '"]').prop('checked', true);

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#holidays-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('holidays.updating'));
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'PUT',
        data: $('#holidays-form').serialize(),
        url: window.HolidaysConfig.baseUrl + '/' + $('#id').val(),
        success: function (data) {
            $('#holidays-modal').modal('hide');
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
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('holidays.delete_confirm_message'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: {_token: CSRF_TOKEN},
            url: window.HolidaysConfig.baseUrl + '/' + id,
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
