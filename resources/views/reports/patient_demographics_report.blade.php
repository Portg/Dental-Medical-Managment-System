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
    .amount { font-family: monospace; }
    @media (max-width: 991px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-people"></i>
                    <span class="caption-subject">{{ __('report.patient_demographics') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 统计卡片 --}}
                <div class="stat-cards">
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $totalPatients }}</div>
                        <div class="stat-label">{{ __('report.total_patients') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $activeVsLost['active'] }}</div>
                        <div class="stat-label">{{ __('report.active_patients') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $activeVsLost['lost'] }}</div>
                        <div class="stat-label">{{ __('report.lost_patients') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $activeVsLost['active_rate'] }}%</div>
                        <div class="stat-label">{{ __('report.active_rate') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 年龄段分布 --}}
                    <div class="col-md-5">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.age_distribution') }}</div>
                            <canvas id="ageChart" height="200"></canvas>
                        </div>
                    </div>

                    {{-- 性别比 --}}
                    <div class="col-md-3">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.gender_distribution') }}</div>
                            <canvas id="genderChart" height="200"></canvas>
                        </div>
                    </div>

                    {{-- 来源分布 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.source_distribution') }}</div>
                            <canvas id="sourceChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 新患月趋势 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.new_patient_trend') }}</div>
                            <canvas id="newPatientTrendChart" height="80"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 高消费 Top 20 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.top_spenders') }}</div>
                            @if($topSpenders->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('report.patient') }}</th>
                                        <th>{{ __('report.gender_col') }}</th>
                                        <th class="text-right">{{ __('report.total_spent') }}</th>
                                        <th class="text-center">{{ __('report.invoice_count') }}</th>
                                        <th>{{ __('report.last_invoice_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($topSpenders as $idx => $s)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $s->patient_name }}</td>
                                        <td>{{ $s->gender }}</td>
                                        <td class="text-right amount">¥{{ number_format($s->total_spent, 2) }}</td>
                                        <td class="text-center">{{ $s->invoice_count }}</td>
                                        <td>{{ $s->last_invoice_date ? \Carbon\Carbon::parse($s->last_invoice_date)->format('Y-m-d') : '-' }}</td>
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
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    var ageColors = ['#1A237E', '#283593', '#303F9F', '#3949AB', '#3F51B5', '#5C6BC0', '#7986CB', '#9FA8DA'];

    // 年龄段分布
    var ageData = {!! json_encode($ageDistribution) !!};
    new Chart(document.getElementById('ageChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ageData.map(function(d) { return d.label; }),
            datasets: [{
                data: ageData.map(function(d) { return d.count; }),
                backgroundColor: ageColors.slice(0, ageData.length)
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 性别比
    var genderData = {!! json_encode($genderDistribution) !!};
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: genderData.map(function(d) { return d.label; }),
            datasets: [{
                data: genderData.map(function(d) { return d.count; }),
                backgroundColor: ['#1A237E', '#E91E63', '#9E9E9E']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // 来源分布
    var sourceData = {!! json_encode($sourceDistribution) !!};
    var srcColors = ['#1A237E', '#3949AB', '#5C6BC0', '#7986CB', '#9FA8DA', '#C5CAE9', '#E8EAF6'];
    new Chart(document.getElementById('sourceChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: sourceData.map(function(d) { return d.source; }),
            datasets: [{
                data: sourceData.map(function(d) { return d.count; }),
                backgroundColor: srcColors.slice(0, sourceData.length)
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // 新患月趋势
    var trendData = {!! json_encode($newPatientTrend) !!};
    new Chart(document.getElementById('newPatientTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: trendData.map(function(d) { return d.month; }),
            datasets: [{
                label: '{{ __("report.new_patients") }}',
                data: trendData.map(function(d) { return d.count; }),
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
</script>
@endsection
