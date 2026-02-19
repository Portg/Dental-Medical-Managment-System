@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/satisfaction-surveys.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-emoticon-smile"></i>
                    <span class="caption-subject">{{ __('satisfaction.survey_detail') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('satisfaction-surveys') }}" class="btn btn-sm btn-default">
                        <i class="icon-arrow-left"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="survey-card">
                            <div class="survey-header">
                                <div class="patient-info">
                                    <h3>
                                        @if($survey->is_anonymous)
                                            {{ __('satisfaction.anonymous') }}
                                        @else
                                            {{ $survey->patient->name ?? '-' }}
                                        @endif
                                    </h3>
                                    <div class="meta">
                                        @if($survey->appointment)
                                            {{ __('satisfaction.appointment_date') }}: {{ $survey->appointment->appointment_date }}
                                        @endif
                                        @if($survey->doctor)
                                            | {{ __('satisfaction.doctor') }}: {{ $survey->doctor->surname }}
                                        @endif
                                    </div>
                                </div>
                                <span class="status {{ $survey->status }}">
                                    {{ __('satisfaction.status.' . $survey->status) }}
                                </span>
                            </div>

                            @if($survey->status == 'completed')
                                <div class="rating-section">
                                    <div class="section-title">{{ __('satisfaction.rating_breakdown') }}</div>
                                    @php
                                        $ratingItems = [
                                            'overall_rating' => __('satisfaction.ratings.overall'),
                                            'service_rating' => __('satisfaction.ratings.service'),
                                            'environment_rating' => __('satisfaction.ratings.environment'),
                                            'wait_time_rating' => __('satisfaction.ratings.wait_time'),
                                            'doctor_rating' => __('satisfaction.ratings.doctor'),
                                        ];
                                    @endphp
                                    @foreach($ratingItems as $field => $label)
                                        <div class="rating-item">
                                            <div class="label">{{ $label }}</div>
                                            <div class="stars">
                                                @if($survey->$field)
                                                    {{ str_repeat('★', $survey->$field) }}{{ str_repeat('☆', 5 - $survey->$field) }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                            <div class="value">{{ $survey->$field ?? '-' }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feedback-section">
                                            <div class="feedback-title">{{ __('satisfaction.feedback') }}</div>
                                            <div class="feedback-content {{ empty($survey->feedback) ? 'empty' : '' }}">
                                                {{ $survey->feedback ?: __('satisfaction.no_feedback') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feedback-section">
                                            <div class="feedback-title">{{ __('satisfaction.suggestions') }}</div>
                                            <div class="feedback-content {{ empty($survey->suggestions) ? 'empty' : '' }}">
                                                {{ $survey->suggestions ?: __('satisfaction.no_suggestions') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted" style="padding: 40px 0;">
                                    <i class="icon-hourglass" style="font-size: 48px; color: #ddd;"></i>
                                    <p style="margin-top: 15px;">{{ __('satisfaction.awaiting_response') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        @if($survey->status == 'completed' && $survey->would_recommend !== null)
                            <div class="nps-section">
                                <div class="nps-value">{{ $survey->would_recommend }}</div>
                                <div class="nps-label">{{ __('satisfaction.would_recommend') }}</div>
                                @php
                                    if ($survey->would_recommend >= 9) {
                                        $npsType = 'promoter';
                                        $npsLabel = __('satisfaction.nps_types.promoter');
                                    } elseif ($survey->would_recommend >= 7) {
                                        $npsType = 'passive';
                                        $npsLabel = __('satisfaction.nps_types.passive');
                                    } else {
                                        $npsType = 'detractor';
                                        $npsLabel = __('satisfaction.nps_types.detractor');
                                    }
                                @endphp
                                <div class="nps-type {{ $npsType }}">{{ $npsLabel }}</div>
                            </div>
                        @endif

                        <div class="survey-card">
                            <div class="section-title" style="margin-bottom: 15px;">{{ __('satisfaction.survey_info') }}</div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">{{ __('satisfaction.channel') }}</div>
                                    <div class="info-value">{{ __('satisfaction.channels.' . $survey->survey_channel) }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">{{ __('satisfaction.date') }}</div>
                                    <div class="info-value">{{ $survey->survey_date ? $survey->survey_date->format('Y-m-d') : '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">{{ __('satisfaction.created_at') }}</div>
                                    <div class="info-value">{{ $survey->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                                @if($survey->branch)
                                    <div class="info-item">
                                        <div class="info-label">{{ __('satisfaction.branch') }}</div>
                                        <div class="info-value">{{ $survey->branch->branch_name }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($survey->status == 'pending')
                            <div class="survey-card text-center">
                                <p class="text-muted">{{ __('satisfaction.resend_hint') }}</p>
                                <button type="button" class="btn btn-primary" id="resendBtn">
                                    <i class="icon-paper-plane"></i> {{ __('satisfaction.resend') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    $('#resendBtn').on('click', function() {
        // TODO: Implement resend functionality
        toastr.info('{{ __("satisfaction.resend_not_implemented") }}');
    });
});
</script>
@endsection
