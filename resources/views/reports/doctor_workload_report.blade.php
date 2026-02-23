@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<style>
    .stat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
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
                    <i class="icon-user"></i>
                    <span class="caption-subject">{{ __('report.doctor_workload') }}</span>
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
                        <div class="stat-value">{{ $totalCompleted }}</div>
                        <div class="stat-label">{{ __('report.completed') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $overallCompletionRate }}%</div>
                        <div class="stat-label">{{ __('report.completion_rate') }}</div>
                    </div>
                </div>

                {{-- 医生排名表 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.doctor_ranking') }}</div>
                            @if($doctorStats->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('report.doctor_name') }}</th>
                                        <th class="text-center">{{ __('report.total_appointments_col') }}</th>
                                        <th class="text-center">{{ __('report.completed_col') }}</th>
                                        <th class="text-center">{{ __('report.cancelled') }}</th>
                                        <th class="text-center">{{ __('report.no_show_col') }}</th>
                                        <th class="text-center">{{ __('report.completion_rate_col') }}</th>
                                        <th class="text-center">{{ __('report.no_show_rate_col') }}</th>
                                        <th class="text-center">{{ __('report.daily_avg') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($doctorStats as $idx => $doc)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $doc->doctor_name }}</td>
                                        <td class="text-center">{{ $doc->total_appointments }}</td>
                                        <td class="text-center">{{ $doc->completed }}</td>
                                        <td class="text-center">{{ $doc->cancelled }}</td>
                                        <td class="text-center">{{ $doc->no_show }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doc->completion_rate >= 80 ? 'good' : ($doc->completion_rate >= 60 ? 'warn' : 'bad') }}">
                                                {{ $doc->completion_rate }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doc->no_show_rate <= 5 ? 'good' : ($doc->no_show_rate <= 15 ? 'warn' : 'bad') }}">
                                                {{ $doc->no_show_rate }}%
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $doc->daily_avg }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 每日工作量趋势 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.daily_workload_trend') }}</div>
                            <canvas id="dailyWorkloadChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 医生预约量排名柱状图 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.doctor_ranking') }}</div>
                            <canvas id="doctorRankingChart" height="80"></canvas>
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

    var trendData = {!! json_encode($dailyTrend) !!};
    var colors = ['#1A237E', '#E91E63', '#4CAF50', '#FF9800', '#9C27B0', '#00BCD4', '#795548', '#607D8B'];

    // 每日工作量趋势（按医生分线）
    var datasets = [];
    trendData.doctors.forEach(function(doctor, i) {
        datasets.push({
            label: doctor,
            data: trendData.dates.map(function(date) {
                return (trendData.data[date] && trendData.data[date][doctor]) || 0;
            }),
            borderColor: colors[i % colors.length],
            backgroundColor: 'transparent',
            tension: 0.3,
            borderWidth: 2
        });
    });

    new Chart(document.getElementById('dailyWorkloadChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: trendData.dates.map(function(d) { return d.substring(5); }),
            datasets: datasets
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { position: 'top' } }
        }
    });

    // 医生预约量排名柱状图
    var doctorData = {!! json_encode($doctorStats) !!};
    new Chart(document.getElementById('doctorRankingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: doctorData.map(function(d) { return d.doctor_name; }),
            datasets: [{
                label: '{{ __("report.completed") }}',
                data: doctorData.map(function(d) { return d.completed; }),
                backgroundColor: '#2E7D32'
            }, {
                label: '{{ __("report.cancelled") }}',
                data: doctorData.map(function(d) { return d.cancelled; }),
                backgroundColor: '#E65100'
            }, {
                label: '{{ __("report.no_show") }}',
                data: doctorData.map(function(d) { return d.no_show; }),
                backgroundColor: '#C62828'
            }]
        },
        options: {
            responsive: true,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { position: 'top' } }
        }
    });
});
</script>
@endsection
