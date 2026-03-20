@if(isset($pendingAppointments) && $pendingAppointments->count() > 0)
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption">
            <i class="fa fa-clock-o font-yellow-gold"></i>
            <span class="caption-subject font-yellow-gold">{{ __('medical_cases.pending_appointments') }}</span>
            <span class="badge badge-warning">{{ $pendingAppointments->count() }}</span>
        </div>
    </div>
    <div class="portlet-body" style="max-height: 200px; overflow-y: auto;">
        @foreach($pendingAppointments as $apt)
        <div class="pending-apt-item"
             onclick="fillFromAppointment({{ $apt->id }}, '{{ $apt->start_date->format('Y-m-d') }}', '{{ $apt->start_time }}', {{ $apt->doctor_id }}, '{{ ($apt->doctor->surname ?? '') . ($apt->doctor->othername ?? '') }}')">
            <div><strong>{{ $apt->start_date->format('Y-m-d') }}</strong> {{ $apt->start_time }}</div>
            <div class="text-muted" style="font-size:11px;">
                {{ ($apt->doctor->surname ?? '') . ($apt->doctor->othername ?? '') }}
                @if($apt->visit_information) &middot; {{ $apt->visit_information }} @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
