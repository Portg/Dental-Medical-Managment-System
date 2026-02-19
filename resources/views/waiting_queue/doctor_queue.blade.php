@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    <style>
        .doctor-queue-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 991px) {
            .doctor-queue-container {
                grid-template-columns: 1fr;
            }
        }

        .current-patient-card {
            background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%);
            color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }
        .current-patient-card .label-text {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        .current-patient-card .patient-name {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .current-patient-card .patient-info {
            font-size: 16px;
            opacity: 0.9;
        }
        .current-patient-card .chair-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            margin-top: 15px;
        }
        .current-patient-card.empty {
            background: #f5f5f5;
            color: #999;
        }
        .current-patient-card .btn-complete {
            margin-top: 20px;
        }

        .queue-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .waiting-list-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .waiting-list-card .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .waiting-list-card .card-header h4 {
            margin: 0;
            font-size: 18px;
        }
        .waiting-list-card .card-header .count {
            background: #FF9800;
            color: #fff;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 14px;
        }

        .patient-queue-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .patient-queue-item:last-child {
            border-bottom: none;
        }
        .patient-queue-item:hover {
            background: #fafafa;
        }
        .patient-queue-item .queue-num {
            width: 50px;
            height: 50px;
            background: #e3f2fd;
            color: #1A237E;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            margin-right: 15px;
        }
        .patient-queue-item:first-child .queue-num {
            background: #FF9800;
            color: #fff;
        }
        .patient-queue-item .info {
            flex: 1;
        }
        .patient-queue-item .name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 3px;
        }
        .patient-queue-item .meta {
            font-size: 13px;
            color: #999;
        }
        .patient-queue-item .wait-time {
            font-size: 14px;
            color: #666;
        }

        .no-patients {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }
    </style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-users"></i>
                    <span class="caption-subject">{{ __('waiting_queue.my_queue') }}</span>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" onclick="callNextPatient()">
                        <i class="icon-volume-2"></i> {{ __('waiting_queue.call_next') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                <div class="doctor-queue-container">
                    {{-- 当前患者 --}}
                    <div>
                        <h5 style="margin-bottom: 15px;">{{ __('waiting_queue.current_patient') }}</h5>
                        @if($inTreatment)
                            <div class="current-patient-card">
                                <div class="label-text">{{ __('waiting_queue.status.in_treatment') }}</div>
                                <div class="patient-name">{{ $inTreatment->patient->name ?? '-' }}</div>
                                <div class="patient-info">
                                    {{ __('waiting_queue.queue_number') }}: {{ $inTreatment->queue_number }}
                                    &nbsp;|&nbsp;
                                    {{ __('waiting_queue.check_in_time') }}: {{ $inTreatment->check_in_time->format('H:i') }}
                                </div>
                                @if($inTreatment->chair)
                                    <div class="chair-badge">{{ $inTreatment->chair->chair_name }}</div>
                                @endif
                                <div class="btn-complete">
                                    <button class="btn btn-success btn-lg" onclick="completeTreatment({{ $inTreatment->id }})">
                                        <i class="icon-check"></i> {{ __('waiting_queue.complete') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="current-patient-card empty">
                                <div class="label-text">{{ __('waiting_queue.current_patient') }}</div>
                                <div class="patient-name">--</div>
                                <div class="patient-info">{{ __('waiting_queue.no_waiting_patients') }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- 候诊队列 --}}
                    <div>
                        <div class="waiting-list-card">
                            <div class="card-header">
                                <h4>{{ __('waiting_queue.waiting_patients') }}</h4>
                                <span class="count">{{ $waitingPatients->count() }}</span>
                            </div>
                            <div class="card-body">
                                @if($waitingPatients->count() > 0)
                                    @foreach($waitingPatients as $patient)
                                        <div class="patient-queue-item">
                                            <div class="queue-num">{{ $patient->queue_number }}</div>
                                            <div class="info">
                                                <div class="name">{{ $patient->patient->name ?? '-' }}</div>
                                                <div class="meta">
                                                    {{ $patient->appointment->appointment_category ?? '-' }}
                                                    @if($patient->status === 'called')
                                                        <span class="label label-info" style="margin-left: 5px;">{{ __('waiting_queue.status.called') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="wait-time">
                                                {{ $patient->waited_minutes }} {{ __('waiting_queue.minutes') }}
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="no-patients">
                                        <i class="icon-emoticon-smile" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                                        {{ __('waiting_queue.no_waiting_patients') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 选择椅位模态框 --}}
<div class="modal fade modal-form" id="chair-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('waiting_queue.call_patient') }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('waiting_queue.select_chair') }}</label>
                    <select class="form-control" id="chair-id">
                        <option value="">{{ __('waiting_queue.no_chair') }}</option>
                        {{-- Chairs will be loaded dynamically --}}
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="doCallNext()">
                    <i class="icon-volume-2"></i> {{ __('waiting_queue.confirm_call') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
function callNextPatient() {
    $('#chair-modal').modal('show');
}

function doCallNext() {
    var chairId = $('#chair-id').val();

    $.post("{{ url('doctor-queue/call-next') }}", {
        _token: '{{ csrf_token() }}',
        chair_id: chairId
    }, function(response) {
        if (response.status === 'success') {
            $('#chair-modal').modal('hide');
            toastr.success(response.message);
            location.reload();
        } else if (response.status === 'info') {
            toastr.info(response.message);
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
            location.reload();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
    });
}

// Auto refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endsection
