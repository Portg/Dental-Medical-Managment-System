$(document).ready(function () {
    loadMedicalCases();
    initializePatientSelect();
    initializeDoctorSelect();

    $('#status').on('change', function() {
        if ($(this).val() === 'Closed') {
            $('#closing_notes_row').show();
        } else {
            $('#closing_notes_row').hide();
        }
    });
});

function initializePatientSelect() {
    $('#patient_id').empty().append('<option value="">' + LanguageManager.trans('medical_cases.select_patient') + '</option>');
    if (typeof patients !== 'undefined') {
        patients.forEach(function(patient) {
            $('#patient_id').append('<option value="' + patient.id + '">' + LanguageManager.joinName(patient.surname, patient.othername) + ' (' + patient.patient_no + ')</option>');
        });
    }
}

function initializeDoctorSelect() {
    $('#doctor_id').empty().append('<option value="">' + LanguageManager.trans('medical_cases.select_doctor') + '</option>');
    if (typeof doctors !== 'undefined') {
        doctors.forEach(function(doctor) {
            $('#doctor_id').append('<option value="' + doctor.id + '">' + LanguageManager.joinName(doctor.surname, doctor.othername) + '</option>');
        });
    }
}

function loadMedicalCases() {
    $('#medical_cases_table').DataTable({
        language: LanguageManager.getDataTableLang(),
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "/medical-cases",
            data: function (d) {}
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'case_no', name: 'case_no'},
            {data: 'title', name: 'title'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'doctor_name', name: 'doctor_name'},
            {data: 'case_date', name: 'case_date'},
            {data: 'statusBadge', name: 'statusBadge', orderable: false, searchable: false},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function addMedicalCase() {
    $('#medical_case_form')[0].reset();
    $('#case_id').val('');
    $('#medical_case_modal_title').text(LanguageManager.trans('medical_cases.add_case'));
    $('#btn_save_case').text(LanguageManager.trans('common.save'));
    $('#closing_notes_row').hide();
    $('#medical_case_modal').modal('show');
}

function editMedicalCase(id) {
    $('.loading').show();
    $('#medical_case_form')[0].reset();
    $.ajax({
        type: 'GET',
        url: '/medical-cases/' + id + '/edit',
        success: function(data) {
            $('#case_id').val(id);
            $('#title').val(data.title);
            $('#case_date').val(data.case_date);
            $('#patient_id').val(data.patient_id);
            $('#doctor_id').val(data.doctor_id);
            $('#status').val(data.status);
            $('#chief_complaint').val(data.chief_complaint);
            $('#history_of_present_illness').val(data.history_of_present_illness);
            $('#closing_notes').val(data.closing_notes);

            if (data.status === 'Closed') {
                $('#closing_notes_row').show();
            } else {
                $('#closing_notes_row').hide();
            }

            $('#medical_case_modal_title').text(LanguageManager.trans('medical_cases.edit_case'));
            $('#btn_save_case').text(LanguageManager.trans('common.update'));
            $('.loading').hide();
            $('#medical_case_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function saveMedicalCase() {
    var id = $('#case_id').val();
    if (id === '') {
        createMedicalCase();
    } else {
        updateMedicalCase(id);
    }
}

function createMedicalCase() {
    $('.loading').show();
    $('#btn_save_case').attr('disabled', true);

    $.ajax({
        type: 'POST',
        url: '/medical-cases',
        data: $('#medical_case_form').serialize(),
        success: function(data) {
            $('#medical_case_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#medical_cases_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_case').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_case').attr('disabled', false);
            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                var errorMsg = '';
                $.each(errors, function(key, value) {
                    errorMsg += value + '\n';
                });
                swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
            }
        }
    });
}

function updateMedicalCase(id) {
    $('.loading').show();
    $('#btn_save_case').attr('disabled', true);

    $.ajax({
        type: 'PUT',
        url: '/medical-cases/' + id,
        data: $('#medical_case_form').serialize(),
        success: function(data) {
            $('#medical_case_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#medical_cases_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_case').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_case').attr('disabled', false);
            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                var errorMsg = '';
                $.each(errors, function(key, value) {
                    errorMsg += value + '\n';
                });
                swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
            }
        }
    });
}

function deleteMedicalCase(id) {
    swal({
        title: LanguageManager.trans('medical_cases.confirm_delete'),
        text: LanguageManager.trans('medical_cases.confirm_delete_message'),
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
            url: '/medical-cases/' + id,
            data: { _token: CSRF_TOKEN },
            success: function(data) {
                $('.loading').hide();
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    $('#medical_cases_table').DataTable().ajax.reload();
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
