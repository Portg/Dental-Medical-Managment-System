$(document).ready(function() {
    loadFollowupsTable();

    // Initialize select2
    $('.select2').select2();
});

function loadFollowupsTable() {
    dataTable = $(getTableSelector()).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-followups',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'followup_no', name: 'followup_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'scheduled_date', name: 'scheduled_date'},
            {data: 'purpose', name: 'purpose'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'overdueFlag', name: 'overdueFlag', orderable: false, searchable: false},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false},
            {data: 'completeBtn', name: 'completeBtn', orderable: false, searchable: false}
        ],
        order: [[4, 'desc']],
        language: LanguageManager.getDataTableLang()
    });

    setupEmptyStateHandler();
}

function addFollowup() {
    resetForm();
    $('#followupModalLabel').text(LanguageManager.trans('patient_followups.add_followup'));
    $('#status_group').hide();
    $('#outcome_group').hide();
    $('#followupModal').modal('show');
}

function resetForm() {
    $('#followupForm')[0].reset();
    $('#followup_id').val('');
    $('#patient_id').val('').trigger('change');
    $('#followupForm .alert-danger').hide();
}

function saveFollowup() {
    var formData = $('#followupForm').serialize();
    var followupId = $('#followup_id').val();
    var url = followupId ? '/patient-followups/' + followupId : '/patient-followups';
    var method = followupId ? 'PUT' : 'POST';

    $('.loading').show();

    $.ajax({
        url: url,
        type: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#followupModal').modal('hide');
                dataTable.ajax.reload();
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
                var errorList = $('#followupForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#followupForm .alert-danger').show();
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

function editFollowup(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-followups/' + id + '/edit',
        type: 'GET',
        success: function(response) {
            $('.loading').hide();
            resetForm();
            $('#followupModalLabel').text(LanguageManager.trans('patient_followups.edit_followup'));
            $('#status_group').show();
            $('#outcome_group').show();

            $('#followup_id').val(response.id);
            $('#patient_id').val(response.patient_id).trigger('change');
            $('#followup_type').val(response.followup_type);
            $('#scheduled_date').val(response.scheduled_date);
            $('#purpose').val(response.purpose);
            $('#status').val(response.status);
            $('#notes').val(response.notes);
            $('#outcome').val(response.outcome);
            $('#next_followup_date').val(response.next_followup_date);

            $('#followupModal').modal('show');
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

function viewFollowup(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-followups/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_followup_no').text(response.followup_no);
            $('#view_followup_type').text(LanguageManager.trans('patient_followups.type_' + response.followup_type.toLowerCase()));
            $('#view_scheduled_date').text(response.scheduled_date);
            $('#view_purpose').text(response.purpose);
            $('#view_notes').text(response.notes || '-');

            var statusClass = 'default';
            if (response.status == 'Pending') statusClass = 'warning';
            else if (response.status == 'Completed') statusClass = 'success';
            else if (response.status == 'Cancelled') statusClass = 'danger';
            else if (response.status == 'No Response') statusClass = 'info';

            $('#view_status').html('<span class="label label-' + statusClass + '">' +
                LanguageManager.trans('patient_followups.status_' + response.status.toLowerCase().replace(' ', '_')) + '</span>');

            if (response.status == 'Completed') {
                $('#view_outcome_row').show();
                $('#view_completed_date_row').show();
                $('#view_outcome').text(response.outcome || '-');
                $('#view_completed_date').text(response.completed_date || '-');
                $('#view_next_followup_date').text(response.next_followup_date || '-');
            } else {
                $('#view_outcome_row').hide();
                $('#view_completed_date_row').hide();
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

function completeFollowup(id) {
    $('#complete_followup_id').val(id);
    $('#complete_outcome').val('');
    $('#completeFollowupModal').modal('show');
}

function confirmCompleteFollowup() {
    var id = $('#complete_followup_id').val();
    var outcome = $('#complete_outcome').val();

    $('.loading').show();
    $.ajax({
        url: '/patient-followups/' + id + '/complete',
        type: 'POST',
        data: {
            outcome: outcome,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#completeFollowupModal').modal('hide');
                dataTable.ajax.reload();
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
                        dataTable.ajax.reload();
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
