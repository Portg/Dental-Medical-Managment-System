@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-list font-green"></i>
                    <span class="caption-subject font-green bold uppercase">{{ __('medical_cases.treatment_plans') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="treatment_plans_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('patient.patient_no') }}</th>
                            <th>{{ __('patient.patient_name') }}</th>
                            <th>{{ __('medical_cases.plan_name') }}</th>
                            <th>{{ __('medical_cases.status') }}</th>
                            <th>{{ __('medical_cases.priority') }}</th>
                            <th>{{ __('medical_cases.estimated_cost') }}</th>
                            <th>{{ __('common.created_at') }}</th>
                            <th>{{ __('common.view') }}</th>
                            <th>{{ __('common.edit') }}</th>
                            <th>{{ __('common.delete') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- View Treatment Plan Modal --}}
@include('medical_cases.treatment_plans.view')

{{-- Create/Edit Treatment Plan Modal --}}
@include('medical_cases.treatment_plans.create')

@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
    $('#treatment_plans_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('treatment-plans') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'patient_no', name: 'patient_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'plan_name', name: 'plan_name'},
            {data: 'statusBadge', name: 'status'},
            {data: 'priorityBadge', name: 'priority'},
            {data: 'estimated_cost', name: 'estimated_cost', render: function(data) {
                return data ? parseFloat(data).toFixed(2) : '-';
            }},
            {data: 'created_at', name: 'created_at', render: function(data) {
                return data ? data.substring(0, 10) : '-';
            }},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        language: LanguageManager.getDataTableLanguage(),
        order: [[7, 'desc']]
    });

    // Show/hide completed fields based on status
    $('#plan_status').on('change', function() {
        var status = $(this).val();
        if (status == 'Completed') {
            $('#actual_cost_row, #actual_completion_date_row, #completion_notes_row').show();
        } else {
            $('#actual_cost_row, #actual_completion_date_row, #completion_notes_row').hide();
        }
    });
});

// View Treatment Plan
function viewTreatmentPlan(id) {
    $.ajax({
        url: '/treatment-plans/' + id,
        type: 'GET',
        success: function(data) {
            $('#view_plan_name').text(data.plan_name || '-');
            $('#view_plan_description').text(data.description || '-');
            $('#view_planned_procedures').text(data.planned_procedures || '-');
            $('#view_estimated_cost').text(data.estimated_cost ? parseFloat(data.estimated_cost).toFixed(2) : '-');
            $('#view_actual_cost').text(data.actual_cost ? parseFloat(data.actual_cost).toFixed(2) : '-');
            // Set status badge
            var statusClass = 'default';
            if (data.status == 'Planned') statusClass = 'info';
            else if (data.status == 'In Progress') statusClass = 'warning';
            else if (data.status == 'Completed') statusClass = 'success';
            else if (data.status == 'Cancelled') statusClass = 'danger';
            $('#view_plan_status').html('<span class="label label-' + statusClass + '">' + (data.status || '-') + '</span>');
            // Set priority badge
            var priorityClass = 'default';
            if (data.priority == 'Low') priorityClass = 'success';
            else if (data.priority == 'Medium') priorityClass = 'info';
            else if (data.priority == 'High') priorityClass = 'warning';
            else if (data.priority == 'Urgent') priorityClass = 'danger';
            $('#view_plan_priority').html('<span class="label label-' + priorityClass + '">' + (data.priority || '-') + '</span>');
            $('#view_start_date').text(data.start_date || '-');
            $('#view_target_date').text(data.target_completion_date || '-');
            if (data.status == 'Completed') {
                $('#view_completion_row').show();
                $('#view_actual_completion_date').text(data.actual_completion_date || '-');
                $('#view_completion_notes').text(data.completion_notes || '-');
            } else {
                $('#view_completion_row').hide();
            }
            $('#view_treatment_plan_modal').modal('show');
        },
        error: function() {
            toastr.error("{{ __('messages.error_occurred') }}");
        }
    });
}

// Edit Treatment Plan
function editTreatmentPlan(id) {
    $('#treatment_plan_modal_title').text("{{ __('medical_cases.edit_treatment_plan') }}");
    $.ajax({
        url: '/treatment-plans/' + id + '/edit',
        type: 'GET',
        success: function(data) {
            $('#treatment_plan_id').val(data.id);
            $('#plan_medical_case_id').val(data.medical_case_id);
            $('#plan_patient_id').val(data.patient_id);
            $('#plan_name').val(data.plan_name);
            $('#plan_description').val(data.description);
            $('#planned_procedures').val(data.planned_procedures);
            $('#estimated_cost').val(data.estimated_cost);
            $('#actual_cost').val(data.actual_cost);
            $('#priority').val(data.priority);
            $('#plan_status').val(data.status).trigger('change');
            $('#start_date').val(data.start_date);
            $('#target_completion_date').val(data.target_completion_date);
            $('#actual_completion_date').val(data.actual_completion_date);
            $('#completion_notes').val(data.completion_notes);
            $('#treatment_plan_modal').modal('show');
        },
        error: function() {
            toastr.error("{{ __('messages.error_occurred') }}");
        }
    });
}

// Save Treatment Plan
function saveTreatmentPlan() {
    var id = $('#treatment_plan_id').val();
    var url = id ? '/treatment-plans/' + id : '/treatment-plans';
    var method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: {
            _token: '{{ csrf_token() }}',
            plan_name: $('#plan_name').val(),
            description: $('#plan_description').val(),
            planned_procedures: $('#planned_procedures').val(),
            estimated_cost: $('#estimated_cost').val(),
            actual_cost: $('#actual_cost').val(),
            status: $('#plan_status').val(),
            priority: $('#priority').val(),
            start_date: $('#start_date').val(),
            target_completion_date: $('#target_completion_date').val(),
            actual_completion_date: $('#actual_completion_date').val(),
            completion_notes: $('#completion_notes').val(),
            medical_case_id: $('#plan_medical_case_id').val(),
            patient_id: $('#plan_patient_id').val()
        },
        success: function(response) {
            if (response.status) {
                toastr.success(response.message);
                $('#treatment_plan_modal').modal('hide');
                $('#treatment_plans_table').DataTable().ajax.reload();
                resetTreatmentPlanForm();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var errorHtml = '<ul>';
                $.each(errors, function(key, value) {
                    errorHtml += '<li>' + value[0] + '</li>';
                });
                errorHtml += '</ul>';
                $('#treatment_plan_modal .alert-danger').html(errorHtml).show();
            } else {
                toastr.error("{{ __('messages.error_occurred') }}");
            }
        }
    });
}

// Delete Treatment Plan
function deleteTreatmentPlan(id) {
    swal({
        title: "{{ __('common.are_you_sure') }}",
        text: "{{ __('medical_cases.confirm_delete_treatment_plan') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "{{ __('common.yes_delete') }}",
        cancelButtonText: "{{ __('common.cancel') }}",
        closeOnConfirm: false
    }, function() {
        $.ajax({
            url: '/treatment-plans/' + id,
            type: 'DELETE',
            data: {_token: '{{ csrf_token() }}'},
            success: function(response) {
                if (response.status) {
                    swal("{{ __('common.deleted') }}", response.message, "success");
                    $('#treatment_plans_table').DataTable().ajax.reload();
                } else {
                    swal("{{ __('common.error') }}", response.message, "error");
                }
            },
            error: function() {
                swal("{{ __('common.error') }}", "{{ __('messages.error_occurred') }}", "error");
            }
        });
    });
}

// Reset form
function resetTreatmentPlanForm() {
    $('#treatment_plan_id').val('');
    $('#treatment_plan_form')[0].reset();
    $('#treatment_plan_modal .alert-danger').hide().find('ul').empty();
    $('#actual_cost_row, #actual_completion_date_row, #completion_notes_row').hide();
}

// Reset on modal close
$('#treatment_plan_modal').on('hidden.bs.modal', function() {
    resetTreatmentPlanForm();
    $('#treatment_plan_modal_title').text("{{ __('medical_cases.add_treatment_plan') }}");
});
</script>
@endsection
