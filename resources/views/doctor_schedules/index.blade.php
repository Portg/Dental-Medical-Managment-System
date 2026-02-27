@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <style>
        .schedule-calendar { margin-top: 20px; }
    </style>
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-calendar"></i>
                    <span class="caption-subject">{{ __('doctor_schedules.title') }}</span>
                </div>
                <div class="actions">
                    <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                </div>
            </div>
            <div class="portlet-body">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#tab_list" data-toggle="tab">{{ __('doctor_schedules.list_view') }}</a>
                    </li>
                    <li>
                        <a href="#tab_calendar" data-toggle="tab">{{ __('doctor_schedules.calendar_view') }}</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab_list">
                        <div class="table-toolbar">
                            <div class="row">
                                <div class="col-md-4">
                                    <select id="filter_doctor" class="form-control">
                                        <option value="">{{ __('doctor_schedules.all_doctors') }}</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered table-hover" id="schedules_table">
                            <thead>
                            <tr>
                                <th>{{ __('common.id') }}</th>
                                <th>{{ __('doctor_schedules.doctor') }}</th>
                                <th>{{ __('doctor_schedules.date') }}</th>
                                <th>{{ __('doctor_schedules.time_range') }}</th>
                                <th>{{ __('doctor_schedules.max_patients') }}</th>
                                <th>{{ __('doctor_schedules.recurring') }}</th>
                                <th>{{ __('doctor_schedules.branch') }}</th>
                                <th>{{ __('common.edit') }}</th>
                                <th>{{ __('common.delete') }}</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="tab_calendar">
                        <div id="schedule_calendar" class="schedule-calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('doctor_schedules.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/fullcalendar.min.js') }}" type="text/javascript"></script>
@if(app()->getLocale() == 'zh-CN')
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/zh-cn.js') }}" type="text/javascript"></script>
@endif
<script type="text/javascript">
$(function () {
    // Initialize datepicker for schedule form
    $('#schedule-modal').on('shown.bs.modal', function () {
        $(this).find('.datepicker').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'yyyy-mm-dd'
        });
    });

    var table = $('#schedules_table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "{{ url('/doctor-schedules/') }}",
            data: function (d) {
                d.doctor_id = $('#filter_doctor').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'doctor_name', name: 'doctor_name'},
            {data: 'schedule_date', name: 'schedule_date'},
            {data: 'time_range', name: 'time_range'},
            {data: 'max_patients', name: 'max_patients'},
            {data: 'recurring_info', name: 'recurring_info'},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });

    $('#filter_doctor').change(function() {
        table.draw();
    });

    // Initialize calendar
    $('a[href="#tab_calendar"]').on('shown.bs.tab', function() {
        if (!$('#schedule_calendar').hasClass('fc')) {
            initCalendar();
        }
    });
});

function initCalendar() {
    $('#schedule_calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        @if(app()->getLocale() == 'zh-CN')
        lang: 'zh-cn',
        @endif
        events: {
            url: "{{ url('/doctor-schedules/calendar') }}",
            type: 'GET'
        },
        eventClick: function(event) {
            editRecord(event.id);
        }
    });
}

function createRecord() {
    $("#schedule-form")[0].reset();
    $('#id').val('');
    $('#recurring_options').hide();
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text('{{ __("common.save_record") }}');
    $('#schedule-modal').modal('show');
}

function save_data() {
    var id = $('#id').val();
    if (id === "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text('{{ __("common.processing") }}');
    $.ajax({
        type: 'POST',
        data: $('#schedule-form').serialize(),
        url: "/doctor-schedules",
        success: function (data) {
            $('#schedule-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_record") }}');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#schedule-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "doctor-schedules/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            $('[name="doctor_id"]').val(data.doctor_id);
            $('[name="schedule_date"]').val(data.schedule_date);
            $('[name="start_time"]').val(data.start_time);
            $('[name="end_time"]').val(data.end_time);
            $('[name="max_patients"]').val(data.max_patients);
            $('[name="branch_id"]').val(data.branch_id);
            $('[name="notes"]').val(data.notes);

            if (data.is_recurring) {
                $('[name="is_recurring"]').prop('checked', true);
                $('#recurring_options').show();
                $('[name="recurring_pattern"]').val(data.recurring_pattern);
                $('[name="recurring_until"]').val(data.recurring_until);
            }

            $.LoadingOverlay("hide");
            $('#btn-save').text('{{ __("common.update_record") }}');
            $('#schedule-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text('{{ __("common.processing") }}');
    $.ajax({
        type: 'PUT',
        data: $('#schedule-form').serialize(),
        url: "/doctor-schedules/" + $('#id').val(),
        success: function (data) {
            $('#schedule-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $.LoadingOverlay("hide");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.update_record") }}');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
        title: "{{ __('common.are_you_sure') }}",
        text: "{{ __('doctor_schedules.delete_confirm') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "{{ __('common.yes_delete_it') }}",
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: "doctor-schedules/" + id,
            success: function (data) {
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    });
}

function alert_dialog(message, status) {
    swal("{{ __('common.alert') }}", message, status);
    if (status) {
        let oTable = $('#schedules_table').dataTable();
        oTable.fnDraw(false);
        if ($('#schedule_calendar').hasClass('fc')) {
            $('#schedule_calendar').fullCalendar('refetchEvents');
        }
    }
}
</script>
@endsection
