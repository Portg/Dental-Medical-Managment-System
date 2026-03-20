$(function () {
    LanguageManager.loadAllFromPHP({
        'charts_of_accounts': window.ChartsOfAccountsConfig.lang,
        'common': window.ChartsOfAccountsConfig.langCommon
    });
});

function createRecord() {
    $("#chart_of_accounts-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#chart_of_accounts-modal').modal('show');
}

function save_data() {
    if ($('#id').val() === "") {
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
        data: $('#chart_of_accounts-form').serialize(),
        url: window.ChartsOfAccountsConfig.storeUrl,
        success: function (data) {
            $('#chart_of_accounts-modal').modal('hide');
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
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#chart_of_accounts-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);

    $.ajax({
        type: 'get',
        url: window.ChartsOfAccountsConfig.editUrl + '/' + id + '/edit',
        success: function (data) {
            $('#id').val(id);
            $('[name="name"]').val(data.name);
            $('[name="description"]').val(data.description);

            $(".account_type").find("option").each(function () {
                if ($(this).val() === data.chart_of_account_category_id) {
                    $(this).prop("selected", "selected");
                }
            });

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#chart_of_accounts-modal').modal('show');
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
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'PUT',
        data: $('#chart_of_accounts-form').serialize(),
        url: window.ChartsOfAccountsConfig.editUrl + '/' + $('#id').val(),
        success: function (data) {
            $('#chart_of_accounts-modal').modal('hide');
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
            json = $.parseJSON(request.responseText);
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
        text: LanguageManager.trans('charts_of_accounts.delete_confirm_message'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        closeOnConfirm: false,
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: {_token: CSRF_TOKEN},
            url: window.ChartsOfAccountsConfig.editUrl + '/' + id,
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

function alert_dialog(message, status) {
    swal(LanguageManager.trans('common.alert'), message, status);
    setTimeout(function () {
        location.reload();
    }, 1900);
}
