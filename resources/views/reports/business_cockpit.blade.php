@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/business-cockpit.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-speedometer"></i>
                    <span class="caption-subject">{{ __('cockpit.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                {{-- Row 1: KPI Cards --}}
                <div class="cockpit-cards">
                    <div class="cockpit-card highlight">
                        <div class="card-value amount">¥{{ number_format($kpi['today_revenue'] / 1000, 1) }}</div>
                        <div class="card-label">{{ __('cockpit.today_revenue') }}（{{ __('cockpit.unit_thousand') }}）</div>
                    </div>
                    <div class="cockpit-card">
                        <div class="card-value amount">¥{{ number_format($kpi['mtd_revenue'] / 1000, 1) }}</div>
                        <div class="card-label">{{ __('cockpit.mtd_revenue') }}（{{ __('cockpit.unit_thousand') }}）</div>
                        <div class="card-change {{ $kpi['mtd_revenue_change'] >= 0 ? 'up' : 'down' }}">
                            {{ $kpi['mtd_revenue_change'] >= 0 ? '↑' : '↓' }} {{ abs($kpi['mtd_revenue_change']) }}%
                        </div>
                    </div>
                    <div class="cockpit-card">
                        <div class="card-value">{{ $kpi['today_appointments'] }}</div>
                        <div class="card-label">{{ __('cockpit.today_appointments') }}</div>
                    </div>
                    <div class="cockpit-card">
                        <div class="card-value">{{ $kpi['today_new_patients'] }}</div>
                        <div class="card-label">{{ __('cockpit.today_new_patients') }}</div>
                    </div>
                    <div class="cockpit-card pending">
                        <div class="card-value">{{ $kpi['pending_count'] }}</div>
                        <div class="card-label">{{ __('cockpit.pending_items') }}</div>
                    </div>
                    <div class="cockpit-card receivable">
                        <div class="card-value amount">¥{{ number_format($kpi['receivables'] / 1000, 1) }}</div>
                        <div class="card-label">{{ __('cockpit.receivables') }}（{{ __('cockpit.unit_thousand') }}）</div>
                    </div>
                </div>

                {{-- Row 2: Revenue Trend + Payment Mix --}}
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.revenue_trend') }}</div>
                            <canvas id="revenueTrendChart" height="100"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.payment_mix') }}</div>
                            <canvas id="paymentMixChart" height="180"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Completion Trend + Doctor Ranking --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.completion_trend') }}</div>
                            <canvas id="completionTrendChart" height="120"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.doctor_ranking') }}</div>
                            <canvas id="doctorRankingChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Row 4: Top Services + Pending Items --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.top_services') }}</div>
                            @if($topServices->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('report.service_name') }}</th>
                                        <th class="text-center">{{ __('report.qty') }}</th>
                                        <th class="text-right">{{ __('report.revenue') }}（{{ __('cockpit.unit_thousand') }}）</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($topServices as $idx => $svc)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $svc->service_name }}</td>
                                        <td class="text-center">{{ (int) $svc->total_qty }}</td>
                                        <td class="text-right amount">¥{{ number_format($svc->total_revenue / 1000, 1) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-box">
                            <div class="box-title">{{ __('cockpit.pending_details') }}</div>
                            @if(count($pendingItems) > 0)
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('cockpit.item_type') }}</th>
                                        <th>{{ __('cockpit.item_desc') }}</th>
                                        <th class="text-center">{{ __('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($pendingItems as $item)
                                    <tr>
                                        <td><span class="pending-type">{{ $item['type'] }}</span></td>
                                        <td>{{ $item['description'] }}</td>
                                        <td class="text-center">
                                            <a href="{{ $item['url'] }}" class="btn btn-xs btn-primary">{{ __('cockpit.go_handle') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('cockpit.no_pending') }}</p>
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
LanguageManager.loadFromPHP(@json(__('cockpit')), 'cockpit');
window.BusinessCockpitConfig = {
    revenueTrend:    @json($revenueTrend),
    paymentMix:      @json($paymentMix),
    completionTrend: @json($completionTrend),
    doctorRanking:   @json($doctorRanking)
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/business_cockpit.js') }}?v={{ filemtime(public_path('include_js/business_cockpit.js')) }}"></script>
@endsection
