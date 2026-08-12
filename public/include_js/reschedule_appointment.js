function RescheduleAppointment(id) {
    $("#reschedule-appointment-form")[0].reset();
    $('#reschedule_appointment_id').val(''); ///always reset hidden form fields
    $('#BtnSave').attr('disabled', false);
    $('#BtnSave').text(LanguageManager.trans('common.save_changes'));

    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        // 必须是绝对路径：这个弹窗现在也长在 /patients/{id} 上，
        // 相对路径会解析成 /patients/appointments/{id}/edit → 404
        url: "/appointments/" + id + "/edit",
        success: function (data) {
            $('#reschedule_appointment_id').val(id);
            $('#reschedule_patient').val(LanguageManager.joinName(data.surname, data.othername));

            $.LoadingOverlay("hide");
            $('#reschedule-appointment-modal').modal('show');

        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });


}

function save_scheduler() {
    $.LoadingOverlay("show");
    $('#BtnSave').attr('disabled', true);
    $('#BtnSave').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#reschedule-appointment-form').serialize(),
        url: "/appointments-reschedule",
        success: function (data) {
            $('#reschedule-appointment-modal').modal('hide');
            $.LoadingOverlay("hide");
            rescheduleDone(data.message, data.status ? "success" : "danger");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#BtnSave').attr('disabled', false);
            $('#BtnSave').text(LanguageManager.trans('common.save_changes'));
            $('#reschedule-appointment-modal').modal('show');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

/*
 * 改约完成后的收尾。
 *
 * 预约页有自己的 alert_dialog（弹提示 + 整页刷新），沿用它以免改动那边的行为。
 * 患者详情页没有 —— alert_dialog 是各个 *_index.js 各自定义的，患者页一个都没引。
 * 那边走下面的兜底：只刷新预约表，不整页刷新，否则会丢掉当前所处的页签。
 */
function rescheduleDone(message, status) {
    if (typeof alert_dialog === 'function') {
        alert_dialog(message, status);
        return;
    }

    swal(LanguageManager.trans('common.notice'), message, status);

    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#patient_appointments_table')) {
        $('#patient_appointments_table').DataTable().ajax.reload(null, false);
    }
}
