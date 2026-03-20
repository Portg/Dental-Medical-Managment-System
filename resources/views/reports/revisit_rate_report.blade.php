@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/revisit-rate.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-action-redo"></i>
                    <span class="caption-subject">{{ __('report.revisit_rate_analysis') }}</span>
                </div>
                <div class="actions">
                    <form class="form-inline" method="GET" style="display: inline-flex; gap: 10px;">
                        <input type="text" name="start_date" class="form-control input-sm datepicker"
                               value="{{ $startDate->format('Y-m-d') }}" placeholder="{{ __('datetime.date_range.start_date') }}">
                        <span>{{ __('datetime.date_range.to') }}</span>
                        <input type="text" name="end_date" class="form-control input-sm datepicker"
                               value="{{ $endDate->format('Y-m-d') }}" placeholder="{{ __('datetime.date_range.end_date') }}">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="icon-magnifier"></i> {{ __('common.search') }}</button>
                    </form>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 统计卡片 --}}
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-value">{{ $currentPeriodPatients }}</div>
                        <div class="stat-label">{{ __('report.total_visit_patients') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $firstVisitPatients }}</div>
                        <div class="stat-label">{{ __('report.first_visit_patients') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $revisitPatients }}</div>
                        <div class="stat-label">{{ __('report.revisit_patients') }}</div>
                    </div>
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $revisitRate }}%</div>
                        <div class="stat-label">{{ __('report.revisit_rate') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 月度趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.monthly_revisit_trend') }}</div>
                            <canvas id="trendChart" height="120"></canvas>
                        </div>
                    </div>

                    {{-- 复诊间隔分布 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.revisit_interval') }}</div>
                            <canvas id="intervalChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- 医生复诊排名 --}}
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.doctor_revisit_ranking') }}</div>
                            @foreach($doctorStats as $index => $doctor)
                                <div class="doctor-rank">
                                    <div class="rank {{ $index < 3 ? 'top' : '' }}">{{ $index + 1 }}</div>
                                    <div class="info">
                                        <div class="name">{{ $doctor->doctor_name }}</div>
                                        <div class="meta">{{ $doctor->total_patients }} {{ __('report.patients') }} / {{ $doctor->total_appointments }} {{ __('report.appointments') }}</div>
                                    </div>
                                    <div class="avg">{{ $doctor->avg_visits_per_patient }} {{ __('report.avg_visits') }}</div>
                                </div>
                            @endforeach
                            @if($doctorStats->isEmpty())
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- 流失患者预警 --}}
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">
                                {{ __('report.lost_patient_alert') }}
                                <small class="text-muted">({{ __('report.no_visit_90_days') }})</small>
                            </div>
                            @foreach($lostPatients as $patient)
                                <div class="lost-patient">
                                    <div>
                                        <strong>{{ $patient->name }}</strong>
                                        <div class="text-muted" style="font-size: 12px;">
                                            {{ __('report.last_visit') }}: {{ $patient->last_visit_date ?? '-' }}
                                        </div>
                                    </div>
                                    <span class="days">{{ $patient->days_since_visit ?? '-' }} {{ __('datetime.days_unit') }}</span>
                                </div>
                            @endforeach
                            @if($lostPatients->isEmpty())
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('report.no_lost_patients') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('report')), 'report');
window.RevisitRateConfig = {
    locale:         '{{ app()->getLocale() }}',
    trendLabels:    @json(array_column($monthlyTrend, 'month_label')),
    trendRates:     @json(array_column($monthlyTrend, 'revisit_rate')),
    intervalLabels: @json(array_column($intervalDistribution, 'label')),
    intervalCounts: @json(array_column($intervalDistribution, 'count'))
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/revisit_rate_report.js') }}?v={{ filemtime(public_path('include_js/revisit_rate_report.js')) }}"></script>
@endsection
