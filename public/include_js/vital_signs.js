$(document).ready(function () {
    $('a[href="#vital_signs_tab"]').on('shown.bs.tab', function() {
        loadVitalSigns();
    });
});

function loadVitalSigns() {
    var url = '/case-vital-signs/' + global_case_id;

    $('#vital_signs_table').DataTable({
        language: LanguageManager.getDataTableLang(),
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            data: function (d) {}
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'recorded_at', name: 'recorded_at'},
            {data: 'blood_pressure', name: 'blood_pressure'},
            {data: 'heart_rate_display', name: 'heart_rate_display'},
            {data: 'temperature_display', name: 'temperature_display'},
            {data: 'added_by', name: 'added_by'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function addVitalSign() {
    $('#vital_sign_form')[0].reset();
    $('#vital_sign_id').val('');
    $('#vital_patient_id').val(global_patient_id);

    // Set current datetime
    var now = new Date();
    var datetime = now.toISOString().slice(0, 16);
    $('#recorded_at').val(datetime);

    $('#vital_sign_modal_title').text(LanguageManager.trans('medical_cases.add_vital_sign'));
    $('#btn_save_vital_sign').text(LanguageManager.trans('common.save'));
    $('#vital_sign_modal').modal('show');
}

function editVitalSign(id) {
    $('.loading').show();
    $('#vital_sign_form')[0].reset();

    $.ajax({
        type: 'GET',
        url: '/vital-signs/' + id + '/edit',
        success: function(data) {
            $('#vital_sign_id').val(id);
            $('#vital_patient_id').val(data.patient_id);

            // Format datetime for input
            if (data.recorded_at) {
                var recordedAt = new Date(data.recorded_at);
                var datetime = recordedAt.toISOString().slice(0, 16);
                $('#recorded_at').val(datetime);
            }

            $('#blood_pressure_systolic').val(data.blood_pressure_systolic);
            $('#blood_pressure_diastolic').val(data.blood_pressure_diastolic);
            $('#heart_rate').val(data.heart_rate);
            $('#temperature').val(data.temperature);
            $('#respiratory_rate').val(data.respiratory_rate);
            $('#oxygen_saturation').val(data.oxygen_saturation);
            $('#weight').val(data.weight);
            $('#height').val(data.height);
            $('#vital_notes').val(data.notes);

            $('#vital_sign_modal_title').text(LanguageManager.trans('medical_cases.edit_vital_sign'));
            $('#btn_save_vital_sign').text(LanguageManager.trans('common.update'));
            $('.loading').hide();
            $('#vital_sign_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function saveVitalSign() {
    var id = $('#vital_sign_id').val();
    if (id === '') {
        createVitalSign();
    } else {
        updateVitalSign(id);
    }
}

function createVitalSign() {
    $('.loading').show();
    $('#btn_save_vital_sign').attr('disabled', true);

    var formData = $('#vital_sign_form').serialize();
    formData += '&notes=' + encodeURIComponent($('#vital_notes').val());

    $.ajax({
        type: 'POST',
        url: '/vital-signs',
        data: formData,
        success: function(data) {
            $('#vital_sign_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#vital_signs_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_vital_sign').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_vital_sign').attr('disabled', false);
            handleVitalSignErrors(request);
        }
    });
}

function updateVitalSign(id) {
    $('.loading').show();
    $('#btn_save_vital_sign').attr('disabled', true);

    var formData = $('#vital_sign_form').serialize();
    formData += '&notes=' + encodeURIComponent($('#vital_notes').val());

    $.ajax({
        type: 'PUT',
        url: '/vital-signs/' + id,
        data: formData,
        success: function(data) {
            $('#vital_sign_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#vital_signs_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_vital_sign').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_vital_sign').attr('disabled', false);
            handleVitalSignErrors(request);
        }
    });
}

function deleteVitalSign(id) {
    swal({
        title: LanguageManager.trans('medical_cases.confirm_delete'),
        text: LanguageManager.trans('medical_cases.confirm_delete_vital_sign'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function() {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $('.loading').show();
        $.ajax({
            type: 'DELETE',
            url: '/vital-signs/' + id,
            data: { _token: CSRF_TOKEN },
            success: function(data) {
                $('.loading').hide();
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    $('#vital_signs_table').DataTable().ajax.reload();
                } else {
                    swal(LanguageManager.trans('common.error'), data.message, "error");
                }
            },
            error: function() {
                $('.loading').hide();
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('messages.error_occurred'), "error");
            }
        });
    });
}

function handleVitalSignErrors(request) {
    if (request.responseJSON && request.responseJSON.errors) {
        var errors = request.responseJSON.errors;
        var errorMsg = '';
        $.each(errors, function(key, value) {
            errorMsg += value + '\n';
        });
        swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
    }
}
