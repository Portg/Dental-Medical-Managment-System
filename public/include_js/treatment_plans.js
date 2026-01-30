$(document).ready(function () {
    $('a[href="#treatment_plans_tab"]').on('shown.bs.tab', function() {
        loadTreatmentPlans();
    });

    $('#plan_status').on('change', function() {
        if ($(this).val() === 'Completed') {
            $('#actual_cost_row').show();
            $('#actual_completion_date_row').show();
            $('#completion_notes_row').show();
        } else {
            $('#actual_cost_row').hide();
            $('#actual_completion_date_row').hide();
            $('#completion_notes_row').hide();
        }
    });
});

function loadTreatmentPlans() {
    var url = '/case-treatment-plans/' + global_case_id;

    $('#treatment_plans_table').DataTable({
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
            {data: 'plan_name', name: 'plan_name'},
            {data: 'priorityBadge', name: 'priorityBadge', orderable: false, searchable: false},
            {data: 'estimated_cost', name: 'estimated_cost', render: function(data) { return data ? parseFloat(data).toFixed(2) : '-'; }},
            {data: 'statusBadge', name: 'statusBadge', orderable: false, searchable: false},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function addTreatmentPlan() {
    $('#treatment_plan_form')[0].reset();
    $('#treatment_plan_id').val('');
    $('#plan_medical_case_id').val(global_case_id);
    $('#plan_patient_id').val(global_patient_id);
    $('#priority').val('Medium');
    $('#plan_status').val('Planned');
    $('#actual_cost_row').hide();
    $('#actual_completion_date_row').hide();
    $('#completion_notes_row').hide();

    $('#treatment_plan_modal_title').text(LanguageManager.trans('medical_cases.add_treatment_plan'));
    $('#btn_save_treatment_plan').text(LanguageManager.trans('common.save'));
    $('#treatment_plan_modal').modal('show');
}

function viewTreatmentPlan(id) {
    $('.loading').show();

    $.ajax({
        type: 'GET',
        url: '/treatment-plans/' + id,
        success: function(data) {
            $('#view_plan_name').text(data.plan_name);
            $('#view_plan_description').text(data.description || '-');
            $('#view_planned_procedures').text(data.planned_procedures || '-');
            $('#view_estimated_cost').text(data.estimated_cost ? parseFloat(data.estimated_cost).toFixed(2) : '-');
            $('#view_actual_cost').text(data.actual_cost ? parseFloat(data.actual_cost).toFixed(2) : '-');
            $('#view_start_date').text(data.start_date || '-');
            $('#view_target_date').text(data.target_completion_date || '-');

            // Status badge
            var statusClass = 'default';
            if (data.status === 'Planned') statusClass = 'info';
            else if (data.status === 'In Progress') statusClass = 'warning';
            else if (data.status === 'Completed') statusClass = 'success';
            else if (data.status === 'Cancelled') statusClass = 'danger';
            $('#view_plan_status').html('<span class="label label-' + statusClass + '">' + LanguageManager.trans('medical_cases.plan_status_' + data.status.toLowerCase().replace(' ', '_')) + '</span>');

            // Priority badge
            var priorityClass = 'default';
            if (data.priority === 'Low') priorityClass = 'success';
            else if (data.priority === 'Medium') priorityClass = 'info';
            else if (data.priority === 'High') priorityClass = 'warning';
            else if (data.priority === 'Urgent') priorityClass = 'danger';
            $('#view_plan_priority').html('<span class="label label-' + priorityClass + '">' + LanguageManager.trans('medical_cases.priority_' + data.priority.toLowerCase()) + '</span>');

            if (data.status === 'Completed') {
                $('#view_completion_row').show();
                $('#view_actual_completion_date').text(data.actual_completion_date || '-');
                $('#view_completion_notes').text(data.completion_notes || '-');
            } else {
                $('#view_completion_row').hide();
            }

            $('.loading').hide();
            $('#view_treatment_plan_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function editTreatmentPlan(id) {
    $('.loading').show();
    $('#treatment_plan_form')[0].reset();

    $.ajax({
        type: 'GET',
        url: '/treatment-plans/' + id + '/edit',
        success: function(data) {
            $('#treatment_plan_id').val(id);
            $('#plan_medical_case_id').val(data.medical_case_id);
            $('#plan_patient_id').val(data.patient_id);
            $('#plan_name').val(data.plan_name);
            $('#plan_description').val(data.description);
            $('#planned_procedures').val(data.planned_procedures);
            $('#estimated_cost').val(data.estimated_cost);
            $('#actual_cost').val(data.actual_cost);
            $('#priority').val(data.priority);
            $('#plan_status').val(data.status);
            $('#start_date').val(data.start_date);
            $('#target_completion_date').val(data.target_completion_date);
            $('#actual_completion_date').val(data.actual_completion_date);
            $('#completion_notes').val(data.completion_notes);

            if (data.status === 'Completed') {
                $('#actual_cost_row').show();
                $('#actual_completion_date_row').show();
                $('#completion_notes_row').show();
            } else {
                $('#actual_cost_row').hide();
                $('#actual_completion_date_row').hide();
                $('#completion_notes_row').hide();
            }

            $('#treatment_plan_modal_title').text(LanguageManager.trans('medical_cases.edit_treatment_plan'));
            $('#btn_save_treatment_plan').text(LanguageManager.trans('common.update'));
            $('.loading').hide();
            $('#treatment_plan_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function saveTreatmentPlan() {
    var id = $('#treatment_plan_id').val();
    if (id === '') {
        createTreatmentPlan();
    } else {
        updateTreatmentPlan(id);
    }
}

function createTreatmentPlan() {
    $('.loading').show();
    $('#btn_save_treatment_plan').attr('disabled', true);

    var formData = $('#treatment_plan_form').serialize();
    formData += '&status=' + $('#plan_status').val();

    $.ajax({
        type: 'POST',
        url: '/treatment-plans',
        data: formData,
        success: function(data) {
            $('#treatment_plan_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#treatment_plans_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_treatment_plan').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_treatment_plan').attr('disabled', false);
            handleTreatmentPlanErrors(request);
        }
    });
}

function updateTreatmentPlan(id) {
    $('.loading').show();
    $('#btn_save_treatment_plan').attr('disabled', true);

    var formData = $('#treatment_plan_form').serialize();
    formData += '&status=' + $('#plan_status').val();

    $.ajax({
        type: 'PUT',
        url: '/treatment-plans/' + id,
        data: formData,
        success: function(data) {
            $('#treatment_plan_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#treatment_plans_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_treatment_plan').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_treatment_plan').attr('disabled', false);
            handleTreatmentPlanErrors(request);
        }
    });
}

function deleteTreatmentPlan(id) {
    swal({
        title: LanguageManager.trans('medical_cases.confirm_delete'),
        text: LanguageManager.trans('medical_cases.confirm_delete_treatment_plan'),
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
            url: '/treatment-plans/' + id,
            data: { _token: CSRF_TOKEN },
            success: function(data) {
                $('.loading').hide();
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    $('#treatment_plans_table').DataTable().ajax.reload();
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

function handleTreatmentPlanErrors(request) {
    if (request.responseJSON && request.responseJSON.errors) {
        var errors = request.responseJSON.errors;
        var errorMsg = '';
        $.each(errors, function(key, value) {
            errorMsg += value + '\n';
        });
        swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
    }
}
