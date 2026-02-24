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
    .table-report th { background: #f5f6fa; font-weight: 600; }
    .badge-rate { padding: 3px 10px; border-radius: 12px; font-size: 12px; }
    .badge-rate.good { background: #E8F5E9; color: #2E7D32; }
    .badge-rate.warn { background: #FFF3E0; color: #E65100; }
    .badge-rate.bad { background: #FFEBEE; color: #C62828; }
    @media (max-width: 991px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-calendar"></i>
                    <span class="caption-subject">{{ __('report.appointment_analytics') }}</span>
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
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $totalAppointments }}</div>
                        <div class="stat-label">{{ __('report.total_appointments') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $completionRate }}%</div>
                        <div class="stat-label">{{ __('report.completion_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $cancellationRate }}%</div>
                        <div class="stat-label">{{ __('report.cancellation_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $noShowRate }}%</div>
                        <div class="stat-label">{{ __('report.no_show_rate') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 每日预约趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.daily_appointment_trend') }}</div>
                            <canvas id="dailyTrendChart" height="120"></canvas>
                        </div>
                    </div>

                    {{-- 就诊高峰时段 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.peak_hours_distribution') }}</div>
                            <canvas id="peakHoursChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- 医生预约统计 --}}
                    <div class="col-md-7">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.by_doctor_breakdown') }}</div>
                            @if($doctorStats->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.doctor_name') }}</th>
                                        <th class="text-center">{{ __('report.total') }}</th>
                                        <th class="text-center">{{ __('report.completed') }}</th>
                                        <th class="text-center">{{ __('report.cancelled') }}</th>
                                        <th class="text-center">{{ __('report.no_show') }}</th>
                                        <th class="text-center">{{ __('report.completion_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($doctorStats as $doctor)
                                    <tr>
                                        <td>{{ $doctor->doctor_name }}</td>
                                        <td class="text-center">{{ $doctor->total }}</td>
                                        <td class="text-center">{{ $doctor->completed }}</td>
                                        <td class="text-center">{{ $doctor->cancelled }}</td>
                                        <td class="text-center">{{ $doctor->no_show }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doctor->completion_rate >= 80 ? 'good' : ($doctor->completion_rate >= 60 ? 'warn' : 'bad') }}">
                                                {{ $doctor->completion_rate }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- 预约来源分布 --}}
                    <div class="col-md-5">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.appointment_source_distribution') }}</div>
                            <canvas id="sourceChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 诊椅使用率 --}}
                @if(count($chairUtilization) > 0)
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.chair_utilization') }}</div>
                            <canvas id="chairChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                @endif
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

    // 每日预约趋势
    new Chart(document.getElementById('dailyTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json(array_column($dailyTrend, 'date')),
            datasets: [{
                label: '{{ __("report.appointments_count") }}',
                data: @json(array_column($dailyTrend, 'count')),
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { display: false } }
        }
    });

    // 就诊高峰时段
    var peakHoursData = @json($peakHours);
    var hourLabels = [];
    var hourValues = [];
    for (var h = 8; h <= 20; h++) {
        hourLabels.push(h + '{{ __("report.hour_suffix") }}');
        hourValues.push(peakHoursData[h] || 0);
    }
    new Chart(document.getElementById('peakHoursChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: hourLabels,
            datasets: [{
                data: hourValues,
                backgroundColor: '#3949AB'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 预约来源分布
    var sourceData = @json($sourceDistribution);
    if (sourceData.length > 0) {
        var sourceColors = ['#1A237E', '#3949AB', '#5C6BC0', '#7986CB', '#9FA8DA', '#C5CAE9', '#E8EAF6'];
        new Chart(document.getElementById('sourceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: sourceData.map(function(d) { return d.source; }),
                datasets: [{
                    data: sourceData.map(function(d) { return d.count; }),
                    backgroundColor: sourceColors.slice(0, sourceData.length)
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // 诊椅使用率
    var chairData = @json($chairUtilization);
    if (chairData.length > 0) {
        new Chart(document.getElementById('chairChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chairData.map(function(d) { return d.chair; }),
                datasets: [{
                    label: '{{ __("report.appointments_count") }}',
                    data: chairData.map(function(d) { return d.count; }),
                    backgroundColor: '#3949AB'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>
@endsection
