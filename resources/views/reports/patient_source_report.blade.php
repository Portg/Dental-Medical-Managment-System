@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<style>
    .source-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .source-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .source-card .source-name { font-size: 14px; color: #666; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
    .source-card .source-name .dot { width: 12px; height: 12px; border-radius: 50%; }
    .source-card .source-count { font-size: 28px; font-weight: bold; color: #1A237E; }
    .source-card .source-meta { font-size: 12px; color: #999; margin-top: 5px; }
    .chart-container { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .chart-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; }
    .progress-bar-wrapper { margin-bottom: 12px; }
    .progress-bar-wrapper .label-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .progress-bar-wrapper .bar { height: 24px; background: #f0f0f0; border-radius: 4px; overflow: hidden; }
    .progress-bar-wrapper .bar-fill { height: 100%; border-radius: 4px; display: flex; align-items: center; padding-left: 10px; color: #fff; font-size: 12px; }
    .conversion-rate { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
    .conversion-rate.high { background: #E8F5E9; color: #2E7D32; }
    .conversion-rate.medium { background: #FFF3E0; color: #E65100; }
    .conversion-rate.low { background: #FFEBEE; color: #C62828; }
</style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-share"></i>
                    <span class="caption-subject">{{ __('report.patient_source_analysis') }}</span>
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
                {{-- 总览卡片 --}}
                <div class="row" style="margin-bottom: 25px;">
                    <div class="col-md-3">
                        <div class="source-card" style="background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); color: #fff;">
                            <div class="source-name" style="color: rgba(255,255,255,0.8);">{{ __('report.total_new_patients') }}</div>
                            <div class="source-count" style="color: #fff;">{{ $totalPatients }}</div>
                            <div class="source-meta" style="color: rgba(255,255,255,0.7);">{{ $startDate->format('Y/m/d') }} - {{ $endDate->format('Y/m/d') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="source-card">
                            <div class="source-name">{{ __('report.source_channels') }}</div>
                            <div class="source-count">{{ count($sourceAnalysis) }}</div>
                            <div class="source-meta">{{ __('report.active_channels') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="source-card">
                            <div class="source-name">{{ __('report.top_source') }}</div>
                            <div class="source-count">{{ $sourceAnalysis[0]['name'] ?? '-' }}</div>
                            <div class="source-meta">{{ $sourceAnalysis[0]['percentage'] ?? 0 }}% {{ __('report.of_total') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="source-card">
                            <div class="source-name">{{ __('report.avg_conversion') }}</div>
                            @php
                                $avgConversion = count($sourceAnalysis) > 0
                                    ? round(array_sum(array_column($sourceAnalysis, 'conversion_rate')) / count($sourceAnalysis), 1)
                                    : 0;
                            @endphp
                            <div class="source-count">{{ $avgConversion }}%</div>
                            <div class="source-meta">{{ __('report.appointment_conversion') }}</div>
                        </div>
                    </div>
                </div>

                {{-- 来源分布图表 --}}
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.source_distribution') }}</div>
                            @foreach($sourceAnalysis as $source)
                                <div class="progress-bar-wrapper">
                                    <div class="label-row">
                                        <span>
                                            <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: {{ $source['color'] }}; margin-right: 5px;"></span>
                                            {{ $source['name'] }}
                                        </span>
                                        <span>
                                            {{ $source['patient_count'] }} {{ __('report.patients') }} ({{ $source['percentage'] }}%)
                                            @php
                                                $rateClass = $source['conversion_rate'] >= 70 ? 'high' : ($source['conversion_rate'] >= 40 ? 'medium' : 'low');
                                            @endphp
                                            <span class="conversion-rate {{ $rateClass }}">{{ __('report.conversion') }}: {{ $source['conversion_rate'] }}%</span>
                                        </span>
                                    </div>
                                    <div class="bar">
                                        <div class="bar-fill" style="width: {{ $source['percentage'] }}%; background: {{ $source['color'] }};">
                                            @if($source['percentage'] > 10){{ $source['patient_count'] }}@endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.source_pie_chart') }}</div>
                            <canvas id="sourcePieChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 来源明细表 --}}
                <div class="chart-container">
                    <div class="chart-title">{{ __('report.source_details') }}</div>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('report.source_name') }}</th>
                                <th>{{ __('report.patient_count') }}</th>
                                <th>{{ __('report.percentage') }}</th>
                                <th>{{ __('report.converted_count') }}</th>
                                <th>{{ __('report.conversion_rate') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sourceAnalysis as $source)
                                <tr>
                                    <td>
                                        <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: {{ $source['color'] }}; margin-right: 5px;"></span>
                                        {{ $source['name'] }}
                                    </td>
                                    <td>{{ $source['patient_count'] }}</td>
                                    <td>{{ $source['percentage'] }}%</td>
                                    <td>{{ $source['converted_count'] }}</td>
                                    <td>
                                        @php
                                            $rateClass = $source['conversion_rate'] >= 70 ? 'high' : ($source['conversion_rate'] >= 40 ? 'medium' : 'low');
                                        @endphp
                                        <span class="conversion-rate {{ $rateClass }}">{{ $source['conversion_rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // 饼图
    var ctx = document.getElementById('sourcePieChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json(array_column($sourceAnalysis, 'name')),
            datasets: [{
                data: @json(array_column($sourceAnalysis, 'patient_count')),
                backgroundColor: @json(array_column($sourceAnalysis, 'color')),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 10 }
                }
            }
        }
    });
});
</script>
@endsection
