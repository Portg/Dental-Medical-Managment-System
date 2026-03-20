// update the bio data of the user
function Update_Biodata() {
    if (confirm(LanguageManager.trans('messages.confirm_save_changes'))) {

        $.LoadingOverlay("show");
        $.ajax({
            type: 'POST',
            data: $('#bio_data').serialize(),
            url: "update-bio",
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
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }
}

// update profile picture
function Update_Avatar() {
    if (confirm(LanguageManager.trans('messages.confirm_save_changes'))) {

        $.LoadingOverlay("show");
        $.ajax({
            type: 'POST',
            data: $('#avatar_form').serialize(),
            url: "update-avatar",
            success: function (data) {
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "success");
                }
                $.LoadingOverlay("hide");
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }
}

// update password
function Change_Password() {
    if (confirm(LanguageManager.trans('messages.confirm_save_changes'))) {

        $.LoadingOverlay("show");
        $.ajax({
            type: 'POST',
            data: $('#passwords_form').serialize(),
            url: "update-password",
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
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }
}

// general alert dialog
function alert_dialog(message, status) {
    toastr[status](message);
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "7000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    setTimeout(function () {
        location.reload();
    }, 1500);
}
