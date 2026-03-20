$(function () {
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
            url: window.DoctorSchedulesConfig.urls.data,
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

    $('a[href="#tab_calendar"]').on('shown.bs.tab', function() {
        if (!$('#schedule_calendar').hasClass('fc')) {
            initCalendar();
        }
    });
});

function initCalendar() {
    var calendarOpts = {
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        events: {
            url: window.DoctorSchedulesConfig.urls.calendar,
            type: 'GET'
        },
        eventClick: function(event) {
            editRecord(event.id);
        }
    };
    if (window.DoctorSchedulesConfig.locale === 'zh-CN') {
        calendarOpts.lang = 'zh-cn';
    }
    $('#schedule_calendar').fullCalendar(calendarOpts);
}

function createRecord() {
    $('#schedule-form')[0].reset();
    $('#id').val('');
    $('#recurring_options').hide();
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#schedule-modal').modal('show');
}

function save_data() {
    var id = $('#id').val();
    if (id === '') {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay('show');
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#schedule-form').serialize(),
        url: window.DoctorSchedulesConfig.urls.base,
        success: function (data) {
            $('#schedule-modal').modal('hide');
            $.LoadingOverlay('hide');
            alert_dialog(data.message, data.status ? 'success' : 'danger');
        },
        error: function (request) {
            $.LoadingOverlay('hide');
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show().append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay('show');
    $('#schedule-form')[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: window.DoctorSchedulesConfig.urls.base + '/' + id + '/edit',
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

            $.LoadingOverlay('hide');
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#schedule-modal').modal('show');
        },
        error: function () {
            $.LoadingOverlay('hide');
        }
    });
}

function update_record() {
    $.LoadingOverlay('show');
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'PUT',
        data: $('#schedule-form').serialize(),
        url: window.DoctorSchedulesConfig.urls.base + '/' + $('#id').val(),
        success: function (data) {
            $('#schedule-modal').modal('hide');
            alert_dialog(data.message, data.status ? 'success' : 'danger');
            $.LoadingOverlay('hide');
        },
        error: function (request) {
            $.LoadingOverlay('hide');
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show().append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('doctor_schedules.delete_confirm'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'delete',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            url: window.DoctorSchedulesConfig.urls.base + '/' + id,
            success: function (data) {
                alert_dialog(data.message, data.status ? 'success' : 'danger');
                $.LoadingOverlay('hide');
            },
            error: function () {
                $.LoadingOverlay('hide');
            }
        });
    });
}

function alert_dialog(message, status) {
    swal(LanguageManager.trans('common.alert'), message, status);
    if (status === 'success') {
        $('#schedules_table').dataTable().fnDraw(false);
        if ($('#schedule_calendar').hasClass('fc')) {
            $('#schedule_calendar').fullCalendar('refetchEvents');
        }
    }
}
