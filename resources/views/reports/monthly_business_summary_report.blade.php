@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/monthly-business-summary.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-graph"></i>
                    <span class="caption-subject">{{ __('report.monthly_business_summary') }}</span>
                </div>
                <div class="actions">
                    <form class="form-inline" method="GET" style="display: inline-flex; gap: 10px;">
                        <input type="month" name="month" class="form-control input-sm"
                               value="{{ $monthStart->format('Y-m') }}">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="icon-magnifier"></i> {{ __('common.search') }}</button>
                    </form>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 统计卡片 --}}
                <div class="stat-cards">
                    <div class="stat-card highlight">
                        <div class="stat-value amount">¥{{ number_format($summary['revenue'], 0) }}</div>
                        <div class="stat-label">{{ __('report.monthly_revenue') }}</div>
                        <div class="stat-change {{ $summary['revenue_change'] >= 0 ? 'up' : 'down' }}">
                            {{ $summary['revenue_change'] >= 0 ? '↑' : '↓' }} {{ abs($summary['revenue_change']) }}%
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value amount">¥{{ number_format($summary['expenses'], 0) }}</div>
                        <div class="stat-label">{{ __('report.monthly_expenses') }}</div>
                        <div class="stat-change {{ $summary['expenses_change'] <= 0 ? 'up' : 'down' }}">
                            {{ $summary['expenses_change'] >= 0 ? '↑' : '↓' }} {{ abs($summary['expenses_change']) }}%
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value amount">¥{{ number_format($summary['gross_profit'], 0) }}</div>
                        <div class="stat-label">{{ __('report.gross_profit') }}</div>
                        <div class="stat-change {{ $summary['gross_profit_change'] >= 0 ? 'up' : 'down' }}">
                            {{ $summary['gross_profit_change'] >= 0 ? '↑' : '↓' }} {{ abs($summary['gross_profit_change']) }}%
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['new_patients'] }}</div>
                        <div class="stat-label">{{ __('report.new_patients') }}</div>
                        <div class="stat-change {{ $summary['new_patients_change'] >= 0 ? 'up' : 'down' }}">
                            {{ $summary['new_patients_change'] >= 0 ? '↑' : '↓' }} {{ abs($summary['new_patients_change']) }}%
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['completion_rate'] }}%</div>
                        <div class="stat-label">{{ __('report.completion_rate') }}</div>
                        <div class="stat-change {{ $summary['completion_rate'] >= $summary['prev_completion_rate'] ? 'up' : 'down' }}">
                            {{ __('report.prev_month') }}: {{ $summary['prev_completion_rate'] }}%
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- 每日收入趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.daily_revenue_trend') }}</div>
                            <canvas id="dailyRevenueChart" height="120"></canvas>
                        </div>
                    </div>

                    {{-- Top 项目收入 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.top_services') }}</div>
                            @if($topServices->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.service_name') }}</th>
                                        <th class="text-center">{{ __('report.qty') }}</th>
                                        <th class="text-right">{{ __('report.revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($topServices as $svc)
                                    <tr>
                                        <td>{{ $svc->service_name }}</td>
                                        <td class="text-center">{{ (int) $svc->total_qty }}</td>
                                        <td class="text-right amount">¥{{ number_format($svc->total_revenue, 2) }}</td>
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
<script>
LanguageManager.loadFromPHP(@json(__('report')), 'report');
window.MonthlyBusinessSummaryConfig = {
    revenueByDay: @json($revenueByDay)
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/monthly_business_summary_report.js') }}?v={{ filemtime(public_path('include_js/monthly_business_summary_report.js')) }}"></script>
@endsection
