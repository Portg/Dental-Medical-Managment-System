@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/waiting-queue.css') }}">
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
LanguageManager.loadFromPHP(@json(__('waiting_queue')), 'waiting_queue');
window.WaitingQueueConfig = {
    csrfToken: '{{ csrf_token() }}',
    urls: {
        data:               '{{ url('waiting-queue/data') }}',
        displayData:        '{{ url('waiting-queue/display-data') }}',
        todayAppointments:  '{{ url('waiting-queue/today-appointments') }}',
        checkIn:            '{{ url('waiting-queue/check-in') }}',
        base:               '{{ url('waiting-queue') }}'
    }
};
</script>
<script src="{{ asset('include_js/waiting_queue.js') }}?v={{ filemtime(public_path('include_js/waiting_queue.js')) }}"></script>
@endsection
