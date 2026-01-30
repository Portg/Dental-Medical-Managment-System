$(document).ready(function () {
    loadDiagnoses();

    $('#diagnosis_status').on('change', function() {
        if ($(this).val() === 'Resolved') {
            $('#resolved_date_row').show();
        } else {
            $('#resolved_date_row').hide();
        }
    });
});

function loadDiagnoses() {
    var url = '/case-diagnoses/' + global_case_id;

    $('#diagnoses_table').DataTable({
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
            {data: 'diagnosis_name', name: 'diagnosis_name'},
            {data: 'icd_code', name: 'icd_code', defaultContent: '-'},
            {data: 'diagnosis_date', name: 'diagnosis_date'},
            {data: 'severityBadge', name: 'severityBadge', orderable: false, searchable: false},
            {data: 'statusBadge', name: 'statusBadge', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function addDiagnosis() {
    $('#diagnosis_form')[0].reset();
    $('#diagnosis_id').val('');
    $('#diagnosis_medical_case_id').val(global_case_id);
    $('#diagnosis_patient_id').val(global_patient_id);
    $('#diagnosis_date').val(new Date().toISOString().split('T')[0]);
    $('#resolved_date_row').hide();
    $('#diagnosis_modal_title').text(LanguageManager.trans('medical_cases.add_diagnosis'));
    $('#btn_save_diagnosis').text(LanguageManager.trans('common.save'));
    $('#diagnosis_modal').modal('show');
}

function editDiagnosis(id) {
    $('.loading').show();
    $('#diagnosis_form')[0].reset();

    $.ajax({
        type: 'GET',
        url: '/diagnoses/' + id + '/edit',
        success: function(data) {
            $('#diagnosis_id').val(id);
            $('#diagnosis_medical_case_id').val(data.medical_case_id);
            $('#diagnosis_patient_id').val(data.patient_id);
            $('#diagnosis_name').val(data.diagnosis_name);
            $('#icd_code').val(data.icd_code);
            $('#diagnosis_date').val(data.diagnosis_date);
            $('#diagnosis_status').val(data.status);
            $('#severity').val(data.severity);
            $('#diagnosis_notes').val(data.notes);

            if (data.status === 'Resolved') {
                $('#resolved_date_row').show();
                $('#resolved_date').val(data.resolved_date);
            } else {
                $('#resolved_date_row').hide();
            }

            $('#diagnosis_modal_title').text(LanguageManager.trans('medical_cases.edit_diagnosis'));
            $('#btn_save_diagnosis').text(LanguageManager.trans('common.update'));
            $('.loading').hide();
            $('#diagnosis_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function saveDiagnosis() {
    var id = $('#diagnosis_id').val();
    if (id === '') {
        createDiagnosis();
    } else {
        updateDiagnosis(id);
    }
}

function createDiagnosis() {
    $('.loading').show();
    $('#btn_save_diagnosis').attr('disabled', true);

    var formData = $('#diagnosis_form').serialize();
    formData += '&status=' + $('#diagnosis_status').val();
    formData += '&notes=' + encodeURIComponent($('#diagnosis_notes').val());

    $.ajax({
        type: 'POST',
        url: '/diagnoses',
        data: formData,
        success: function(data) {
            $('#diagnosis_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#diagnoses_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_diagnosis').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_diagnosis').attr('disabled', false);
            handleValidationErrors(request);
        }
    });
}

function updateDiagnosis(id) {
    $('.loading').show();
    $('#btn_save_diagnosis').attr('disabled', true);

    var formData = $('#diagnosis_form').serialize();
    formData += '&status=' + $('#diagnosis_status').val();
    formData += '&notes=' + encodeURIComponent($('#diagnosis_notes').val());

    $.ajax({
        type: 'PUT',
        url: '/diagnoses/' + id,
        data: formData,
        success: function(data) {
            $('#diagnosis_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#diagnoses_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_diagnosis').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_diagnosis').attr('disabled', false);
            handleValidationErrors(request);
        }
    });
}

function deleteDiagnosis(id) {
    swal({
        title: LanguageManager.trans('medical_cases.confirm_delete'),
        text: LanguageManager.trans('medical_cases.confirm_delete_diagnosis'),
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
            url: '/diagnoses/' + id,
            data: { _token: CSRF_TOKEN },
            success: function(data) {
                $('.loading').hide();
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    $('#diagnoses_table').DataTable().ajax.reload();
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

function handleValidationErrors(request) {
    if (request.responseJSON && request.responseJSON.errors) {
        var errors = request.responseJSON.errors;
        var errorMsg = '';
        $.each(errors, function(key, value) {
            errorMsg += value + '\n';
        });
        swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
    }
}
