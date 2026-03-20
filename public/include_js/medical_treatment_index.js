function save_appointment_status() {
    swal({
            title: LanguageManager.trans('medical_treatment.are_you_sure_save'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn green-meadow",
            confirmButtonText: LanguageManager.trans('medical_treatment.yes_save'),
            closeOnConfirm: false
        },
        function () {
            $.LoadingOverlay("show");
            $('#btn-appointment-status').attr('disabled', true);
            $('#btn-appointment-status').text(LanguageManager.trans('common.processing'));
            $.ajax({
                type: 'POST',
                data: $('#appointment-status-form').serialize(),
                url: "/appointment-status",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    swal(LanguageManager.trans('common.alert'), data.message, "success");
                    setTimeout(function () {
                        location.replace('/doctor-appointments');
                    }, 1900);
                },
                error: function (error) {
                    $.LoadingOverlay("hide");
                    $('#btn-appointment-status').attr('disabled', false);
                    $('#btn-appointment-status').text(LanguageManager.trans('common.save'));
                }
            });
        });
}
