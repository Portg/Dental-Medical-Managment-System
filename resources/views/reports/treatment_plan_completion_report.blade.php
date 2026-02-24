@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<style>
    .stat-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
    .stat-card .stat-value { font-size: 32px; font-weight: bold; color: #1A237E; }
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
    .amount { font-family: monospace; }
    @media (max-width: 991px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-chart"></i>
                    <span class="caption-subject">{{ __('report.treatment_plan_completion') }}</span>
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
                        <div class="stat-value">{{ $summary['total_quotations'] }}</div>
                        <div class="stat-label">{{ __('report.total_quotations') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['converted_count'] }}</div>
                        <div class="stat-label">{{ __('report.converted_count') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['conversion_rate'] }}%</div>
                        <div class="stat-label">{{ __('report.conversion_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value amount">¥{{ number_format($summary['total_quotation_amount'], 0) }}</div>
                        <div class="stat-label">{{ __('report.total_quoted_amount') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['avg_conversion_days'] }}{{ __('report.days_unit') }}</div>
                        <div class="stat-label">{{ __('report.avg_conversion_days') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 月度转化趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.monthly_conversion_trend') }}</div>
                            <canvas id="monthlyTrendChart" height="120"></canvas>
                        </div>
                    </div>

                    {{-- 按医生统计 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.by_doctor_conversion') }}</div>
                            <canvas id="doctorConversionChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 按项目分类完成率 --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.by_service_category') }}</div>
                            @if($byServiceCategory->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.service_name') }}</th>
                                        <th>{{ __('report.category') }}</th>
                                        <th class="text-center">{{ __('report.quoted_count') }}</th>
                                        <th class="text-center">{{ __('report.converted_count') }}</th>
                                        <th class="text-center">{{ __('report.conversion_rate') }}</th>
                                        <th class="text-right">{{ __('report.quoted_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($byServiceCategory as $row)
                                    <tr>
                                        <td>{{ $row->service_name }}</td>
                                        <td>{{ $row->category }}</td>
                                        <td class="text-center">{{ $row->quoted_count }}</td>
                                        <td class="text-center">{{ $row->converted_count }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $row->conversion_rate >= 60 ? 'good' : ($row->conversion_rate >= 30 ? 'warn' : 'bad') }}">
                                                {{ $row->conversion_rate }}%
                                            </span>
                                        </td>
                                        <td class="text-right amount">¥{{ number_format($row->quoted_amount, 2) }}</td>
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

                {{-- 医生转化统计表 --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.doctor_conversion_table') }}</div>
                            @if($byDoctor->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.doctor_name') }}</th>
                                        <th class="text-center">{{ __('report.total_quotations') }}</th>
                                        <th class="text-center">{{ __('report.converted_count') }}</th>
                                        <th class="text-center">{{ __('report.conversion_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($byDoctor as $doc)
                                    <tr>
                                        <td>{{ $doc->doctor_name }}</td>
                                        <td class="text-center">{{ $doc->total_quotations }}</td>
                                        <td class="text-center">{{ $doc->converted_count }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doc->conversion_rate >= 60 ? 'good' : ($doc->conversion_rate >= 30 ? 'warn' : 'bad') }}">
                                                {{ $doc->conversion_rate }}%
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

                    {{-- 未转化高价值报价单 --}}
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.unconverted_high_value') }}</div>
                            @if($unconvertedHighValue->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.quotation_no') }}</th>
                                        <th>{{ __('report.patient') }}</th>
                                        <th class="text-right">{{ __('report.amount') }}</th>
                                        <th class="text-center">{{ __('report.days_since_quoted') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($unconvertedHighValue as $q)
                                    <tr>
                                        <td>{{ $q->quotation_no }}</td>
                                        <td>{{ $q->patient_name }}</td>
                                        <td class="text-right amount">¥{{ number_format($q->total_amount, 2) }}</td>
                                        <td class="text-center">{{ $q->days_since_quoted }}{{ __('report.days_unit') }}</td>
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
    $('.datepicker').datepicker({ language: '{{ app()->getLocale() }}', format: 'yyyy-mm-dd', autoclose: true });

    // 月度转化趋势
    var trendData = @json($monthlyTrend);
    new Chart(document.getElementById('monthlyTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: trendData.map(function(d) { return d.month; }),
            datasets: [{
                label: '{{ __("report.total_quotations") }}',
                data: trendData.map(function(d) { return d.total; }),
                backgroundColor: 'rgba(26, 35, 126, 0.3)',
                borderColor: '#1A237E',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '{{ __("report.converted_count") }}',
                data: trendData.map(function(d) { return d.converted; }),
                backgroundColor: 'rgba(46, 125, 50, 0.3)',
                borderColor: '#2E7D32',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '{{ __("report.conversion_rate") }}',
                data: trendData.map(function(d) { return d.rate; }),
                type: 'line',
                borderColor: '#E65100',
                backgroundColor: 'transparent',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, position: 'left', ticks: { stepSize: 1 } },
                y1: { beginAtZero: true, position: 'right', max: 100, grid: { drawOnChartArea: false },
                    ticks: { callback: function(v) { return v + '%'; } }
                }
            }
        }
    });

    // 按医生转化率（水平条形图）
    var doctorData = @json($byDoctor);
    if (doctorData.length > 0) {
        new Chart(document.getElementById('doctorConversionChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: doctorData.map(function(d) { return d.doctor_name; }),
                datasets: [{
                    label: '{{ __("report.conversion_rate") }}',
                    data: doctorData.map(function(d) { return d.conversion_rate; }),
                    backgroundColor: doctorData.map(function(d) {
                        return d.conversion_rate >= 60 ? '#2E7D32' : (d.conversion_rate >= 30 ? '#E65100' : '#C62828');
                    })
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } } }
            }
        });
    }
});
</script>
@endsection
