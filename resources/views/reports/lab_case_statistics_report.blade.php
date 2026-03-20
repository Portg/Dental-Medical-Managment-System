@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/lab-statistics.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-chemistry"></i>
                    <span class="caption-subject">{{ __('report.lab_statistics_report') }}</span>
                </div>
                <div class="actions">
                    <form class="form-inline" method="GET" style="display: inline-flex; gap: 10px;">
                        <input type="text" name="start_date" class="form-control input-sm datepicker"
                               value="{{ $startDate }}" placeholder="{{ __('report.start_date') }}">
                        <span>-</span>
                        <input type="text" name="end_date" class="form-control input-sm datepicker"
                               value="{{ $endDate }}" placeholder="{{ __('report.end_date') }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="icon-magnifier"></i> {{ __('common.search') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="portlet-body">

                {{-- 汇总卡片 --}}
                <div class="stat-cards">
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $summary['total'] }}</div>
                        <div class="stat-label">{{ __('report.total_lab_cases') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['completed'] }}</div>
                        <div class="stat-label">{{ __('report.completed') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['completion_rate'] }}%</div>
                        <div class="stat-label">{{ __('report.completion_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['rework_count'] }}</div>
                        <div class="stat-label">{{ __('report.rework_count') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['avg_days'] }}{{ __('report.days_unit') }}</div>
                        <div class="stat-label">{{ __('report.avg_processing_days') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">¥{{ number_format($summary['total_lab_fee'], 0) }}</div>
                        <div class="stat-label">{{ __('report.total_lab_fee') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 按状态分布 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.by_status') }}</div>
                            <canvas id="statusChart" height="200"></canvas>
                        </div>
                    </div>

                    {{-- 月度趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.monthly_trend') }}</div>
                            <canvas id="trendChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 按技工所统计 --}}
                <div class="chart-container">
                    <div class="chart-title">{{ __('report.by_lab') }}</div>
                    @if($byLab->isNotEmpty())
                    <table class="table table-striped table-bordered table-report">
                        <thead>
                            <tr>
                                <th>{{ __('report.lab_name') }}</th>
                                <th class="text-center">{{ __('report.total') }}</th>
                                <th class="text-center">{{ __('report.completed') }}</th>
                                <th class="text-right">{{ __('report.avg_fee') }}</th>
                                <th class="text-right">{{ __('report.total_fee') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($byLab as $row)
                            <tr>
                                <td>{{ $row->lab_name }}</td>
                                <td class="text-center">{{ $row->total }}</td>
                                <td class="text-center">{{ $row->completed }}</td>
                                <td class="text-right">¥{{ number_format($row->avg_fee, 0) }}</td>
                                <td class="text-right">¥{{ number_format($row->total_fee, 0) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @else
                        <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                    @endif
                </div>

                {{-- 按医生统计 --}}
                <div class="chart-container">
                    <div class="chart-title">{{ __('report.by_doctor') }}</div>
                    @if($byDoctor->isNotEmpty())
                    <table class="table table-striped table-bordered table-report">
                        <thead>
                            <tr>
                                <th>{{ __('report.doctor_name') }}</th>
                                <th class="text-center">{{ __('report.total') }}</th>
                                <th class="text-center">{{ __('report.completed') }}</th>
                                <th class="text-center">{{ __('report.rework_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($byDoctor as $row)
                            <tr>
                                <td>{{ $row->doctor_name }}</td>
                                <td class="text-center">{{ $row->total }}</td>
                                <td class="text-center">{{ $row->completed }}</td>
                                <td class="text-center">{{ $row->rework_count }}</td>
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
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('report')), 'report');
    LanguageManager.loadFromPHP(@json(__('lab_cases')), 'lab_cases');
    window.LabStatisticsConfig = {
        locale:       '{{ app()->getLocale() }}',
        byStatus:     @json($byStatus),
        monthlyTrend: @json($monthlyTrend)
    };
</script>
<script src="{{ asset('include_js/lab_case_statistics_report.js') }}?v={{ filemtime(public_path('include_js/lab_case_statistics_report.js')) }}"></script>
@endsection
