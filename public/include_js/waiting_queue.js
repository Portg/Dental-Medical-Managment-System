var queueTable;
var selectedAppointmentId = null;

$(document).ready(function() {
    initQueueTable();
    loadStats();
    loadCurrentCalling();

    setInterval(function() {
        refreshQueue();
        loadStats();
        loadCurrentCalling();
    }, 30000);

    $('#filter_status').on('change', function() {
        queueTable.ajax.reload();
    });
});

function initQueueTable() {
    queueTable = $('#queue-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.WaitingQueueConfig.urls.data,
            data: function(d) {
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            { data: 'queue_number', name: 'queue_number' },
            { data: 'patient_name', name: 'patient_name' },
            { data: 'patient_phone', name: 'patient_phone' },
            { data: 'doctor_name', name: 'doctor_name' },
            { data: 'check_in_time_formatted', name: 'check_in_time' },
            { data: 'waited_minutes', name: 'waited_minutes', render: function(data) {
                return data + ' ' + LanguageManager.trans('waiting_queue.minutes');
            }},
            { data: 'status_badge', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        language: LanguageManager.getDataTableLang()
    });
}

function refreshQueue() {
    queueTable.ajax.reload(null, false);
    loadStats();
    loadCurrentCalling();
}

function loadStats() {
    $.get(window.WaitingQueueConfig.urls.displayData, function(response) {
        $('#stat-waiting').text(response.stats.waiting_count);
        $('#stat-in-treatment').text(response.stats.in_treatment_count);
        $('#stat-completed').text(response.stats.completed_count);
    });
}

function loadCurrentCalling() {
    $.get(window.WaitingQueueConfig.urls.displayData, function(response) {
        var container = $('#current-calling');
        if (response.current_calling) {
            container.removeClass('empty');
            container.html(
                '<div class="call-label">' + LanguageManager.trans('waiting_queue.current_calling') + '</div>' +
                '<div class="call-number">' + response.current_calling.queue_number + '</div>' +
                '<div class="call-info">' +
                    response.current_calling.patient_name +
                    ' &nbsp;|&nbsp; ' +
                    (response.current_calling.doctor_name || '') +
                    (response.current_calling.chair_name ? ' - ' + response.current_calling.chair_name : '') +
                '</div>'
            );
        } else {
            container.addClass('empty');
            container.html(
                '<div class="call-label">' + LanguageManager.trans('waiting_queue.current_calling') + '</div>' +
                '<div class="call-number">--</div>' +
                '<div class="call-info">' + LanguageManager.trans('waiting_queue.no_current_calling') + '</div>'
            );
        }
    });
}

function openCheckInModal() {
    selectedAppointmentId = null;
    $('#btn-check-in').prop('disabled', true);
    $('#check-in-modal').modal('show');

    $.get(window.WaitingQueueConfig.urls.todayAppointments, function(response) {
        var html = '';
        if (response.data.length === 0) {
            html = '<p class="text-muted text-center">' + LanguageManager.trans('waiting_queue.no_appointments_today') + '</p>';
        } else {
            response.data.forEach(function(apt) {
                html +=
                    '<div class="appointment-item" data-id="' + apt.id + '">' +
                        '<div class="row">' +
                            '<div class="col-md-2"><strong>' + apt.time + '</strong></div>' +
                            '<div class="col-md-3">' + apt.patient_name + '</div>' +
                            '<div class="col-md-3">' + apt.patient_phone + '</div>' +
                            '<div class="col-md-2">' + apt.doctor_name + '</div>' +
                            '<div class="col-md-2">' + apt.category + '</div>' +
                        '</div>' +
                    '</div>';
            });
        }
        $('#appointment-list').html(html);

        $('.appointment-item').on('click', function() {
            $('.appointment-item').removeClass('selected');
            $(this).addClass('selected');
            selectedAppointmentId = $(this).data('id');
            $('#btn-check-in').prop('disabled', false);
        });
    });
}

function doCheckIn() {
    if (!selectedAppointmentId) return;

    $.post(window.WaitingQueueConfig.urls.checkIn, {
        _token: window.WaitingQueueConfig.csrfToken,
        appointment_id: selectedAppointmentId
    }, function(response) {
        if (response.status === 'success') {
            $('#check-in-modal').modal('hide');
            toastr.success(response.message + ' - ' + LanguageManager.trans('waiting_queue.queue_number') + ': ' + response.data.queue_number);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

function callPatient(id) {
    $('#call-queue-id').val(id);
    $('#call-modal').modal('show');
}

function doCallPatient() {
    var id = $('#call-queue-id').val();
    var chairId = $('#call-chair-id').val();

    $.post(window.WaitingQueueConfig.urls.base + '/' + id + '/call', {
        _token: window.WaitingQueueConfig.csrfToken,
        chair_id: chairId
    }, function(response) {
        if (response.status === 'success') {
            $('#call-modal').modal('hide');
            toastr.success(response.message);
            refreshQueue();
            loadCurrentCalling();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

function recallPatient(id) {
    callPatient(id);
}

function startTreatment(id) {
    $.post(window.WaitingQueueConfig.urls.base + '/' + id + '/start', {
        _token: window.WaitingQueueConfig.csrfToken
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

function completeTreatment(id) {
    $.post(window.WaitingQueueConfig.urls.base + '/' + id + '/complete', {
        _token: window.WaitingQueueConfig.csrfToken
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

function cancelQueue(id) {
    if (!confirm(LanguageManager.trans('waiting_queue.confirm_cancel'))) return;

    $.post(window.WaitingQueueConfig.urls.base + '/' + id + '/cancel', {
        _token: window.WaitingQueueConfig.csrfToken
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}
