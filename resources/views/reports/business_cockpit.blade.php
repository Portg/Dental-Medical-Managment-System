@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<style>
    .cockpit-cards { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px; }
    .cockpit-card { background: #fff; border-radius: 8px; padding: 18px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
    .cockpit-card .card-value { font-size: 26px; font-weight: bold; color: #1A237E; }
    .cockpit-card .card-value.amount { font-family: monospace; }
    .cockpit-card .card-unit { font-size: 14px; font-weight: normal; margin-left: 2px; opacity: 0.7; }
    .cockpit-card .card-label { font-size: 13px; color: #666; margin-top: 4px; }
    .cockpit-card .card-change { font-size: 12px; margin-top: 4px; }
    .cockpit-card .card-change.up { color: #2E7D32; }
    .cockpit-card .card-change.down { color: #C62828; }
    .cockpit-card.highlight { background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); }
    .cockpit-card.highlight .card-value,
    .cockpit-card.highlight .card-label { color: #fff; }
    .cockpit-card.highlight .card-change.up { color: #A5D6A7; }
    .cockpit-card.highlight .card-change.down { color: #EF9A9A; }
    .cockpit-card.pending .card-value { color: #E65100; }
    .cockpit-card.receivable .card-value { color: #C62828; }
    .chart-box { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .chart-box .box-title { font-size: 15px; font-weight: 600; margin-bottom: 14px; color: #333; }
    .table-report th { background: #f5f6fa; font-weight: 600; font-size: 13px; }
    .table-report td { font-size: 13px; }
    .table-report .amount { font-family: monospace; }
    .pending-type { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; background: #FFF3E0; color: #E65100; }
    @media (max-width: 1200px) { .cockpit-cards { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .cockpit-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    var revenueTrend = @json($revenueTrend);
    var paymentMix = @json($paymentMix);
    var completionTrend = @json($completionTrend);
    var doctorRanking = @json($doctorRanking);

    // ── Revenue Trend (bar) ─────────────────────────────────────
    new Chart(document.getElementById('revenueTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueTrend.map(function(d) { return d.date.substring(5); }),
            datasets: [{
                label: '{{ __("cockpit.daily_revenue") }}',
                data: revenueTrend.map(function(d) { return +(d.revenue / 1000).toFixed(2); }),
                backgroundColor: 'rgba(26, 35, 126, 0.6)',
                borderColor: '#1A237E',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return '\u00A5' + (ctx.raw * 1000).toLocaleString(); } } } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: '{{ __("cockpit.unit_thousand") }}' }, ticks: { callback: function(v) { return v.toLocaleString(); } } }
            }
        }
    });

    // ── Payment Mix (doughnut) ──────────────────────────────────
    var mixColors = {
        'Cash': '#3598DC', 'WeChat': '#09BB07', 'Alipay': '#1677FF',
        'BankCard': '#F5A623', 'StoredValue': '#8E44AD', 'Insurance': '#2ECC71',
        'Online Wallet': '#E67E22', 'Mobile Money': '#1ABC9C', 'Cheque': '#95A5A6',
        'Self Account': '#E74C3C', 'Credit': '#E74C3C'
    };
    var defaultColors = ['#5C6BC0','#7986CB','#9FA8DA','#C5CAE9','#E8EAF6','#3949AB','#1A237E','#283593','#303F9F','#3F51B5'];
    new Chart(document.getElementById('paymentMixChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: paymentMix.map(function(d) { return d.payment_method; }),
            datasets: [{
                data: paymentMix.map(function(d) { return parseFloat(d.total); }),
                backgroundColor: paymentMix.map(function(d, i) { return mixColors[d.payment_method] || defaultColors[i % defaultColors.length]; })
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } },
                tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': \u00A5' + (ctx.raw / 1000).toFixed(1) + '{{ __("cockpit.unit_thousand") }}'; } } }
            }
        }
    });

    // ── Completion Trend (line) ─────────────────────────────────
    new Chart(document.getElementById('completionTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: completionTrend.map(function(d) { return d.date.substring(5); }),
            datasets: [{
                label: '{{ __("cockpit.completion_rate") }}',
                data: completionTrend.map(function(d) { return d.rate; }),
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } }
            }
        }
    });

    // ── Doctor Ranking (horizontal bar) ─────────────────────────
    new Chart(document.getElementById('doctorRankingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: doctorRanking.map(function(d) { return d.doctor_name; }),
            datasets: [{
                label: '{{ __("cockpit.revenue") }}',
                data: doctorRanking.map(function(d) { return +(parseFloat(d.revenue) / 1000).toFixed(2); }),
                backgroundColor: '#3949AB'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return '\u00A5' + (ctx.raw * 1000).toLocaleString(); } } } },
            scales: {
                x: { beginAtZero: true, title: { display: true, text: '{{ __("cockpit.unit_thousand") }}' }, ticks: { callback: function(v) { return v.toLocaleString(); } } }
            }
        }
    });
});
</script>
@endsection
