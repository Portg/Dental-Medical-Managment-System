$(document).ready(function() {
    loadPatientAppointments();
    loadPatientCases();
    loadPatientImages();
    loadPatientInvoices();
    loadPatientFollowups();
});

// Load Patient Appointments
function loadPatientAppointments() {
    $('#patient_appointments_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/appointments',
            type: 'GET',
            data: function(d) {
                d.patient_id = global_patient_id;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'appointment_no', name: 'appointment_no'},
            {data: 'sort_by', name: 'sort_by'},
            {data: 'doctor_name', name: 'doctor_name', defaultContent: '-'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Medical Cases
function loadPatientCases() {
    $('#patient_cases_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-medical-cases/' + global_patient_id,
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'case_no', name: 'case_no'},
            {data: 'title', name: 'title'},
            {data: 'case_date', name: 'case_date'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Images
function loadPatientImages() {
    $('#patient_images_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-images/' + global_patient_id + '/list',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'title', name: 'title'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'image_date', name: 'image_date'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Invoices
function loadPatientInvoices() {
    $('#patient_invoices_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-invoices/' + global_patient_id,
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'invoice_no', name: 'invoice_no'},
            {data: 'created_at', name: 'created_at'},
            {data: 'amount', name: 'amount'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Followups
function loadPatientFollowups() {
    $('#patient_followups_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-followups/' + global_patient_id + '/list',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'scheduled_date', name: 'scheduled_date'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'purpose', name: 'purpose'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[1, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Add Patient Image
function addPatientImage() {
    $('#patientImageForm')[0].reset();
    $('#patientImageForm .alert-danger').hide();
    $('#addImageModal').modal('show');
}

function savePatientImage() {
    var formData = new FormData($('#patientImageForm')[0]);

    $('.loading').show();

    $.ajax({
        url: '/patient-images',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#addImageModal').modal('hide');
                $('#patient_images_table').DataTable().ajax.reload();
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#patientImageForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#patientImageForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function viewImage(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-images/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_image_title').text(response.title);
            $('#view_image_type').text(response.image_type);
            $('#view_image_date').text(response.image_date);
            $('#view_tooth_number').text(response.tooth_number || '-');
            $('#view_image_description').text(response.description || '-');

            $('#view_image_src').attr('src', '/' + response.file_path);
            $('#download_image_btn').attr('href', '/' + response.file_path);

            $('#viewImageModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function deleteImage(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('patient_images.delete_confirmation'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/patient-images/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_images_table').DataTable().ajax.reload();
                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: response.message,
                            type: 'success'
                        });
                    } else {
                        swal({
                            title: LanguageManager.trans('messages.error'),
                            text: response.message,
                            type: 'error'
                        });
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: LanguageManager.trans('messages.error_occurred'),
                        type: 'error'
                    });
                }
            });
        }
    });
}

// Add Patient Followup
function addPatientFollowup() {
    $('#patientFollowupForm')[0].reset();
    $('#patientFollowupForm .alert-danger').hide();
    $('#addFollowupModal').modal('show');
}

function savePatientFollowup() {
    var formData = $('#patientFollowupForm').serialize();

    $('.loading').show();

    $.ajax({
        url: '/patient-followups',
        type: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#addFollowupModal').modal('hide');
                $('#patient_followups_table').DataTable().ajax.reload();
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#patientFollowupForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#patientFollowupForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function viewFollowup(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-followups/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_followup_no').text(response.followup_no);
            var typeKey = 'patient_followups.type_' + response.followup_type.toLowerCase();
            $('#view_followup_type').text(LanguageManager.trans(typeKey) || response.followup_type);
            $('#view_scheduled_date').text(response.scheduled_date);
            $('#view_followup_purpose').text(response.purpose);
            $('#view_followup_notes').text(response.notes || '-');

            var statusClass = 'default';
            if (response.status == 'Pending') statusClass = 'warning';
            else if (response.status == 'Completed') statusClass = 'success';
            else if (response.status == 'Cancelled') statusClass = 'danger';
            else if (response.status == 'No Response') statusClass = 'info';

            var statusKey = 'patient_followups.status_' + response.status.toLowerCase().replace(/ /g, '_');
            var statusText = LanguageManager.trans(statusKey) || response.status;
            $('#view_followup_status').html('<span class="label label-' + statusClass + '">' + statusText + '</span>');

            if (response.status == 'Completed' && response.outcome) {
                $('#view_outcome_row').show();
                $('#view_followup_outcome').text(response.outcome);
            } else {
                $('#view_outcome_row').hide();
            }

            $('#viewFollowupModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function deleteFollowup(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('patient_followups.delete_confirmation'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/patient-followups/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_followups_table').DataTable().ajax.reload();
                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: response.message,
                            type: 'success'
                        });
                    } else {
                        swal({
                            title: LanguageManager.trans('messages.error'),
                            text: response.message,
                            type: 'error'
                        });
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: LanguageManager.trans('messages.error_occurred'),
                        type: 'error'
                    });
                }
            });
        }
    });
}
