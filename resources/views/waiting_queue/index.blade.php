@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    <style>
        .queue-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #1A237E;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .stat-card.waiting .stat-number { color: #FF9800; }
        .stat-card.called .stat-number { color: #2196F3; }
        .stat-card.in-treatment .stat-number { color: #1A237E; }
        .stat-card.completed .stat-number { color: #4CAF50; }

        .current-calling {
            background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%);
            color: #fff;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .current-calling .call-label {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .current-calling .call-number {
            font-size: 72px;
            font-weight: bold;
            line-height: 1;
        }
        .current-calling .call-info {
            font-size: 18px;
            margin-top: 15px;
        }
        .current-calling.empty {
            background: #f5f5f5;
            color: #999;
        }

        .check-in-modal .appointment-item {
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 6px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .check-in-modal .appointment-item:hover {
            background: #f0f1fa;
            border-color: #3949AB;
        }
        .check-in-modal .appointment-item.selected {
            background: #e8eaf6;
            border-color: #1A237E;
        }
    </style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-users"></i>
                    <span class="caption-subject">{{ __('waiting_queue.title') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('waiting-queue/display') }}" target="_blank" class="btn btn-default btn-sm">
                        <i class="icon-screen-desktop"></i> {{ __('waiting_queue.open_display_screen') }}
                    </a>
                    <button class="btn btn-primary btn-sm" onclick="openCheckInModal()">
                        <i class="icon-login"></i> {{ __('waiting_queue.patient_check_in') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 统计卡片 --}}
                <div class="queue-stats">
                    <div class="stat-card waiting">
                        <div class="stat-number" id="stat-waiting">0</div>
                        <div class="stat-label">{{ __('waiting_queue.status.waiting') }}</div>
                    </div>
                    <div class="stat-card called">
                        <div class="stat-number" id="stat-called">0</div>
                        <div class="stat-label">{{ __('waiting_queue.status.called') }}</div>
                    </div>
                    <div class="stat-card in-treatment">
                        <div class="stat-number" id="stat-in-treatment">0</div>
                        <div class="stat-label">{{ __('waiting_queue.status.in_treatment') }}</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-number" id="stat-completed">0</div>
                        <div class="stat-label">{{ __('waiting_queue.status.completed') }}</div>
                    </div>
                </div>

                {{-- 当前叫号 --}}
                <div class="current-calling empty" id="current-calling">
                    <div class="call-label">{{ __('waiting_queue.current_calling') }}</div>
                    <div class="call-number">--</div>
                    <div class="call-info">{{ __('waiting_queue.no_current_calling') }}</div>
                </div>

                {{-- 筛选 --}}
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-3">
                        <select class="form-control" id="filter_status">
                            <option value="">{{ __('common.all') }}</option>
                            <option value="waiting">{{ __('waiting_queue.status.waiting') }}</option>
                            <option value="called">{{ __('waiting_queue.status.called') }}</option>
                            <option value="in_treatment">{{ __('waiting_queue.status.in_treatment') }}</option>
                            <option value="completed">{{ __('waiting_queue.status.completed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-default" onclick="refreshQueue()">
                            <i class="icon-refresh"></i> {{ __('common.refresh') }}
                        </button>
                    </div>
                </div>

                {{-- 队列表格 --}}
                <table class="table table-striped table-bordered table-hover" id="queue-table">
                    <thead>
                        <tr>
                            <th>{{ __('waiting_queue.queue_number') }}</th>
                            <th>{{ __('patient.patient_name') }}</th>
                            <th>{{ __('patient.phone') }}</th>
                            <th>{{ __('appointment.doctor') }}</th>
                            <th>{{ __('waiting_queue.check_in_time') }}</th>
                            <th>{{ __('waiting_queue.waited_time') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 签到模态框 --}}
<div class="modal fade modal-form modal-form-lg" id="check-in-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('waiting_queue.patient_check_in') }}</h4>
            </div>
            <div class="modal-body check-in-modal">
                <div class="form-group">
                    <label>{{ __('waiting_queue.select_appointment') }}</label>
                    <div id="appointment-list">
                        <p class="text-muted">{{ __('common.loading') }}...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="doCheckIn()" id="btn-check-in" disabled>
                    {{ __('waiting_queue.confirm_check_in') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 叫号选择椅位模态框 --}}
<div class="modal fade modal-form" id="call-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('waiting_queue.call_patient') }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="call-queue-id">
                <div class="form-group">
                    <label>{{ __('waiting_queue.select_chair') }}</label>
                    <select class="form-control" id="call-chair-id">
                        <option value="">{{ __('waiting_queue.no_chair') }}</option>
                        @foreach($chairs as $chair)
                            <option value="{{ $chair->id }}">{{ $chair->chair_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="doCallPatient()">
                    <i class="icon-volume-2"></i> {{ __('waiting_queue.confirm_call') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
var queueTable;
var selectedAppointmentId = null;

$(document).ready(function() {
    initQueueTable();
    loadStats();
    loadCurrentCalling();

    // 每30秒自动刷新
    setInterval(function() {
        refreshQueue();
        loadStats();
        loadCurrentCalling();
    }, 30000);

    // 状态筛选
    $('#filter_status').on('change', function() {
        queueTable.ajax.reload();
    });
});

function initQueueTable() {
    queueTable = $('#queue-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('waiting-queue/data') }}",
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
                return data + ' {{ __("waiting_queue.minutes") }}';
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
    $.get("{{ url('waiting-queue/display-data') }}", function(response) {
        $('#stat-waiting').text(response.stats.waiting_count);
        $('#stat-in-treatment').text(response.stats.in_treatment_count);
        $('#stat-completed').text(response.stats.completed_count);

        // 计算已叫号数
        var calledCount = response.waiting_list ? 0 : 0; // 需要从后端获取
    });
}

function loadCurrentCalling() {
    $.get("{{ url('waiting-queue/display-data') }}", function(response) {
        var container = $('#current-calling');
        if (response.current_calling) {
            container.removeClass('empty');
            container.html(`
                <div class="call-label">{{ __('waiting_queue.current_calling') }}</div>
                <div class="call-number">${response.current_calling.queue_number}</div>
                <div class="call-info">
                    ${response.current_calling.patient_name}
                    &nbsp;|&nbsp;
                    ${response.current_calling.doctor_name || ''}
                    ${response.current_calling.chair_name ? ' - ' + response.current_calling.chair_name : ''}
                </div>
            `);
        } else {
            container.addClass('empty');
            container.html(`
                <div class="call-label">{{ __('waiting_queue.current_calling') }}</div>
                <div class="call-number">--</div>
                <div class="call-info">{{ __('waiting_queue.no_current_calling') }}</div>
            `);
        }
    });
}

function openCheckInModal() {
    selectedAppointmentId = null;
    $('#btn-check-in').prop('disabled', true);
    $('#check-in-modal').modal('show');

    // 加载今日预约
    $.get("{{ url('waiting-queue/today-appointments') }}", function(response) {
        var html = '';
        if (response.data.length === 0) {
            html = '<p class="text-muted text-center">{{ __("waiting_queue.no_appointments_today") }}</p>';
        } else {
            response.data.forEach(function(apt) {
                html += `
                    <div class="appointment-item" data-id="${apt.id}">
                        <div class="row">
                            <div class="col-md-2"><strong>${apt.time}</strong></div>
                            <div class="col-md-3">${apt.patient_name}</div>
                            <div class="col-md-3">${apt.patient_phone}</div>
                            <div class="col-md-2">${apt.doctor_name}</div>
                            <div class="col-md-2">${apt.category}</div>
                        </div>
                    </div>
                `;
            });
        }
        $('#appointment-list').html(html);

        // 点击选择
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

    $.post("{{ url('waiting-queue/check-in') }}", {
        _token: '{{ csrf_token() }}',
        appointment_id: selectedAppointmentId
    }, function(response) {
        if (response.status === 'success') {
            $('#check-in-modal').modal('hide');
            toastr.success(response.message + ' - {{ __("waiting_queue.queue_number") }}: ' + response.data.queue_number);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}

function callPatient(id) {
    $('#call-queue-id').val(id);
    $('#call-modal').modal('show');
}

function doCallPatient() {
    var id = $('#call-queue-id').val();
    var chairId = $('#call-chair-id').val();

    $.post("{{ url('waiting-queue') }}/" + id + "/call", {
        _token: '{{ csrf_token() }}',
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
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}

function recallPatient(id) {
    // 重新叫号
    callPatient(id);
}

function startTreatment(id) {
    $.post("{{ url('waiting-queue') }}/" + id + "/start", {
        _token: '{{ csrf_token() }}'
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}

function completeTreatment(id) {
    $.post("{{ url('waiting-queue') }}/" + id + "/complete", {
        _token: '{{ csrf_token() }}'
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}

function cancelQueue(id) {
    if (!confirm('{{ __("waiting_queue.confirm_cancel") }}')) return;

    $.post("{{ url('waiting-queue') }}/" + id + "/cancel", {
        _token: '{{ csrf_token() }}'
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            refreshQueue();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}
</script>
@endsection
