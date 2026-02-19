@extends('layouts.list-page')

@section('page_title', __('lab_cases.lab_case_list'))
@section('table_id', 'lab-cases-table')

@section('header_actions')
    <a href="#" onclick="createLabCase()" class="btn btn-primary">
        <i class="fa fa-plus"></i> {{ __('lab_cases.add_lab_case') }}
    </a>
    <a href="{{ url('labs') }}" class="btn btn-default">
        <i class="fa fa-building"></i> {{ __('lab_cases.lab_management') }}
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('lab_cases.status') }}</div>
            <select class="form-control" id="filter_status">
                <option value="">{{ __('lab_cases.all_statuses') }}</option>
                @foreach(\App\LabCase::STATUSES as $key => $label)
                    <option value="{{ $key }}">{{ __('lab_cases.status_' . $key) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('lab_cases.lab_name_header') }}</div>
            <select class="form-control" id="filter_lab">
                <option value="">{{ __('lab_cases.all_labs') }}</option>
                @foreach($labs as $lab)
                    <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">
                {{ __('common.reset') }}
            </button>
            <button type="button" id="filterBtn" class="btn btn-primary">
                {{ __('common.search') }}
            </button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('lab_cases.id') }}</th>
    <th>{{ __('lab_cases.lab_case_no') }}</th>
    <th>{{ __('lab_cases.patient_name') }}</th>
    <th>{{ __('lab_cases.doctor_name') }}</th>
    <th>{{ __('lab_cases.lab_name_header') }}</th>
    <th>{{ __('lab_cases.prosthesis_type') }}</th>
    <th>{{ __('lab_cases.status') }}</th>
    <th>{{ __('lab_cases.expected_return_date') }}</th>
    <th></th>
    <th>{{ __('lab_cases.actions') }}</th>
@endsection

@section('modals')
    @include('lab_cases.create_modal')
    @include('lab_cases.edit_modal')
    @include('lab_cases.status_modal')
@endsection

@section('page_js')
<script type="text/javascript">
$(function () {
    LanguageManager.loadAllFromPHP({
        'lab_cases': @json(__('lab_cases'))
    });

    dataTable = $('#lab-cases-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "{{ url('/lab-cases/') }}",
            data: function (d) {
                d.status = $('#filter_status').val();
                d.lab_id = $('#filter_lab').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'lab_case_no', name: 'lab_case_no'},
            {data: 'patient_name', name: 'patient_name', orderable: false, searchable: false},
            {data: 'doctor_name', name: 'doctor_name', orderable: false, searchable: false},
            {data: 'lab_name', name: 'lab_name', orderable: false, searchable: false},
            {data: 'prosthesis_type_label', name: 'prosthesis_type', orderable: false},
            {data: 'status_label', name: 'status', orderable: false},
            {data: 'expected_return_date', name: 'expected_return_date'},
            {data: 'overdue_flag', name: 'overdue_flag', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();

    $('#filterBtn').click(function () {
        dataTable.draw(true);
    });
});

function createLabCase() {
    $("#create-lab-case-form")[0].reset();
    $('.alert-danger').hide().find('ul').html('');
    $('#create-lab-case-modal').modal('show');
}

function saveLabCase() {
    $.LoadingOverlay("show");
    $('#btn-create').attr('disabled', true).text('{{ __("common.processing") }}');
    $.ajax({
        type: 'POST',
        data: $('#create-lab-case-form').serialize(),
        url: "{{ url('lab-cases') }}",
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#create-lab-case-modal').modal('hide');
            swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-create').attr('disabled', false).text('{{ __("common.save_changes") }}');
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

function editLabCase(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'GET',
        url: "{{ url('api/lab-case') }}/" + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#edit_id').val(data.id);
            $('#edit_prosthesis_type').val(data.prosthesis_type);
            $('#edit_material').val(data.material);
            $('#edit_color_shade').val(data.color_shade);
            $('#edit_teeth_positions').val(data.teeth_positions ? (Array.isArray(data.teeth_positions) ? data.teeth_positions.join(', ') : data.teeth_positions) : '');
            $('#edit_special_requirements').val(data.special_requirements);
            $('#edit_expected_return_date').val(data.expected_return_date);
            $('#edit_lab_fee').val(data.lab_fee);
            $('#edit_patient_charge').val(data.patient_charge);
            $('#edit_quality_rating').val(data.quality_rating);
            $('#edit_notes').val(data.notes);
            $('.alert-danger').hide().find('ul').html('');
            $('#edit-lab-case-modal').modal('show');
        },
        error: function () {
            $.LoadingOverlay("hide");
        }
    });
}

function updateLabCase() {
    var id = $('#edit_id').val();
    $.LoadingOverlay("show");
    $('#btn-update').attr('disabled', true).text('{{ __("common.processing") }}');
    $.ajax({
        type: 'PUT',
        data: $('#edit-lab-case-form').serialize(),
        url: "{{ url('lab-cases') }}/" + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#edit-lab-case-modal').modal('hide');
            swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-update').attr('disabled', false).text('{{ __("common.save_changes") }}');
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

function updateStatus(id) {
    $('#status_case_id').val(id);
    $('#status_value').val('');
    $('#rework_reason_group').hide();
    $('#status_rework_reason').val('');
    $('.alert-danger').hide().find('ul').html('');
    $('#status-modal').modal('show');
}

function saveStatus() {
    var id = $('#status_case_id').val();
    $.LoadingOverlay("show");
    $('#btn-status').attr('disabled', true).text('{{ __("common.processing") }}');
    $.ajax({
        type: 'POST',
        data: $('#status-form').serialize(),
        url: "{{ url('lab-cases') }}/" + id + "/update-status",
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#status-modal').modal('hide');
            swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-status').attr('disabled', false).text('{{ __("common.save_changes") }}');
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

function deleteLabCase(id) {
    swal({
        title: "{{ __('lab_cases.are_you_sure') }}",
        text: "{{ __('lab_cases.confirm_delete_case') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "{{ __('lab_cases.yes_delete_it') }}",
        cancelButtonText: "{{ __('lab_cases.cancel') }}",
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'DELETE',
            data: {_token: CSRF_TOKEN},
            url: "{{ url('lab-cases') }}/" + id,
            success: function (data) {
                $.LoadingOverlay("hide");
                swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                if (data.status) {
                    dataTable.draw(false);
                }
            },
            error: function () {
                $.LoadingOverlay("hide");
            }
        });
    });
}

$('#status_value').on('change', function () {
    if ($(this).val() === 'rework') {
        $('#rework_reason_group').show();
    } else {
        $('#rework_reason_group').hide();
        $('#status_rework_reason').val('');
    }
});

// Patient search select2
$('#create_patient_id').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('lab_cases.select_patient') }}",
    minimumInputLength: 2,
    ajax: {
        url: '/search-patient',
        dataType: 'json',
        data: function (params) { return { q: $.trim(params.term) }; },
        processResults: function (data) { return { results: data }; },
        cache: true
    }
});

// Doctor search select2
$('#create_doctor_id').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('lab_cases.select_doctor') }}",
    minimumInputLength: 2,
    ajax: {
        url: '/search-doctor',
        dataType: 'json',
        data: function (params) { return { q: $.trim(params.term) }; },
        processResults: function (data) { return { results: data }; },
        cache: true
    }
});
</script>
@endsection
