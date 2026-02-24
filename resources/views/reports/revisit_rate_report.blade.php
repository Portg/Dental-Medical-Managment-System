@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<style>
    .stat-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
    .stat-card .stat-value { font-size: 36px; font-weight: bold; color: #1A237E; }
    .stat-card .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
    .stat-card.highlight { background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); }
    .stat-card.highlight .stat-value, .stat-card.highlight .stat-label { color: #fff; }
    .chart-container { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .chart-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; }
    .doctor-rank { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .doctor-rank:last-child { border-bottom: none; }
    .doctor-rank .rank { width: 30px; height: 30px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; }
    .doctor-rank .rank.top { background: #FFD700; color: #333; }
    .doctor-rank .info { flex: 1; }
    .doctor-rank .name { font-weight: 500; }
    .doctor-rank .meta { font-size: 12px; color: #999; }
    .doctor-rank .avg { font-weight: bold; color: #1A237E; }
    .lost-patient { padding: 12px 0; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; }
    .lost-patient:last-child { border-bottom: none; }
    .lost-patient .days { background: #FFEBEE; color: #C62828; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: auto; }
    @media (max-width: 991px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    $('.datepicker').datepicker({ language: '{{ app()->getLocale() }}', format: 'yyyy-mm-dd', autoclose: true });

    // 月度趋势图
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: @json(array_column($monthlyTrend, 'month_label')),
            datasets: [{
                label: '{{ __("report.revisit_rate") }}',
                data: @json(array_column($monthlyTrend, 'revisit_rate')),
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 复诊间隔分布图
    var intervalCtx = document.getElementById('intervalChart').getContext('2d');
    new Chart(intervalCtx, {
        type: 'bar',
        data: {
            labels: @json(array_column($intervalDistribution, 'label')),
            datasets: [{
                data: @json(array_column($intervalDistribution, 'count')),
                backgroundColor: ['#4CAF50', '#8BC34A', '#FFC107', '#FF9800', '#FF5722', '#F44336']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
@endsection
