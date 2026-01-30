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
    .stat-card.nps-good { background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%); }
    .stat-card.nps-good .stat-value, .stat-card.nps-good .stat-label { color: #fff; }
    .stat-card.nps-neutral { background: linear-gradient(135deg, #F57C00 0%, #FF9800 100%); }
    .stat-card.nps-neutral .stat-value, .stat-card.nps-neutral .stat-label { color: #fff; }
    .stat-card.nps-bad { background: linear-gradient(135deg, #C62828 0%, #F44336 100%); }
    .stat-card.nps-bad .stat-value, .stat-card.nps-bad .stat-label { color: #fff; }
    .chart-container { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .chart-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; }
    .rating-bar { display: flex; align-items: center; margin-bottom: 12px; }
    .rating-bar .label { width: 100px; font-size: 13px; color: #666; }
    .rating-bar .bar-wrapper { flex: 1; height: 24px; background: #f0f0f0; border-radius: 4px; overflow: hidden; margin: 0 10px; }
    .rating-bar .bar { height: 100%; border-radius: 4px; background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); }
    .rating-bar .value { width: 50px; text-align: right; font-weight: bold; color: #1A237E; }
    .doctor-rank { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
    .doctor-rank:last-child { border-bottom: none; }
    .doctor-rank .rank { width: 30px; height: 30px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; }
    .doctor-rank .rank.top { background: #FFD700; color: #333; }
    .doctor-rank .info { flex: 1; }
    .doctor-rank .name { font-weight: 500; }
    .doctor-rank .meta { font-size: 12px; color: #999; }
    .doctor-rank .rating { font-weight: bold; color: #1A237E; }
    .rating-dist-item { display: flex; align-items: center; margin-bottom: 8px; }
    .rating-dist-item .stars { width: 80px; color: #FFD700; }
    .rating-dist-item .bar-wrapper { flex: 1; height: 20px; background: #f0f0f0; border-radius: 4px; overflow: hidden; margin: 0 10px; }
    .rating-dist-item .bar { height: 100%; border-radius: 4px; }
    .rating-dist-item .count { width: 40px; text-align: right; font-size: 13px; color: #666; }
    @media (max-width: 991px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }
</style>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-emotsmile"></i>
                    <span class="caption-subject">{{ __('satisfaction.title') }}</span>
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
                {{-- NPS 和总览卡片 --}}
                <div class="stat-cards">
                    @php
                        $npsClass = 'highlight';
                        if ($nps !== null) {
                            if ($nps >= 50) $npsClass = 'nps-good';
                            elseif ($nps >= 0) $npsClass = 'nps-neutral';
                            else $npsClass = 'nps-bad';
                        }
                    @endphp
                    <div class="stat-card {{ $npsClass }}">
                        <div class="stat-value">{{ $nps ?? '-' }}</div>
                        <div class="stat-label">NPS {{ __('satisfaction.score') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $avgRatings['overall'] ?? '-' }}</div>
                        <div class="stat-label">{{ __('satisfaction.overall_rating') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $totalSurveys }}</div>
                        <div class="stat-label">{{ __('satisfaction.completed_surveys') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $pendingSurveys }}</div>
                        <div class="stat-label">{{ __('satisfaction.pending_surveys') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 各项评分 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('satisfaction.rating_breakdown') }}</div>
                            @php
                                $ratingLabels = [
                                    'overall' => __('satisfaction.ratings.overall'),
                                    'service' => __('satisfaction.ratings.service'),
                                    'environment' => __('satisfaction.ratings.environment'),
                                    'wait_time' => __('satisfaction.ratings.wait_time'),
                                    'doctor' => __('satisfaction.ratings.doctor'),
                                ];
                            @endphp
                            @foreach($ratingLabels as $key => $label)
                                <div class="rating-bar">
                                    <div class="label">{{ $label }}</div>
                                    <div class="bar-wrapper">
                                        <div class="bar" style="width: {{ (($avgRatings[$key] ?? 0) / 5) * 100 }}%;"></div>
                                    </div>
                                    <div class="value">{{ $avgRatings[$key] ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- 评分分布 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('satisfaction.rating_distribution') }}</div>
                            @for($i = 5; $i >= 1; $i--)
                                @php
                                    $count = $ratingDistribution[$i]['count'] ?? 0;
                                    $percentage = $totalSurveys > 0 ? ($count / $totalSurveys) * 100 : 0;
                                    $colors = ['#4CAF50', '#8BC34A', '#FFC107', '#FF9800', '#F44336'];
                                @endphp
                                <div class="rating-dist-item">
                                    <div class="stars">{{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</div>
                                    <div class="bar-wrapper">
                                        <div class="bar" style="width: {{ $percentage }}%; background: {{ $colors[5 - $i] }};"></div>
                                    </div>
                                    <div class="count">{{ $count }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- 月度趋势 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('satisfaction.monthly_trend') }}</div>
                            <canvas id="trendChart" height="180"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- 医生评分排名 --}}
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('satisfaction.doctor_ranking') }}</div>
                            @foreach($doctorRankings as $index => $ranking)
                                <div class="doctor-rank">
                                    <div class="rank {{ $index < 3 ? 'top' : '' }}">{{ $index + 1 }}</div>
                                    <div class="info">
                                        <div class="name">{{ $ranking->doctor->surname ?? '-' }}</div>
                                        <div class="meta">{{ $ranking->count }} {{ __('satisfaction.reviews') }}</div>
                                    </div>
                                    <div class="rating">
                                        <span style="color: #FFD700;">★</span> {{ number_format($ranking->avg_rating, 1) }}
                                    </div>
                                </div>
                            @endforeach
                            @if($doctorRankings->isEmpty())
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- 调查列表 --}}
                    <div class="col-md-6">
                        <div class="chart-container">
                            <div class="chart-title">
                                {{ __('satisfaction.recent_surveys') }}
                                <a href="#surveyTable" class="btn btn-xs btn-primary pull-right">{{ __('common.view_all') }}</a>
                            </div>
                            <div id="recentSurveys"></div>
                        </div>
                    </div>
                </div>

                {{-- 调查数据表格 --}}
                <div class="chart-container" id="surveyTable">
                    <div class="chart-title">
                        {{ __('satisfaction.survey_list') }}
                        <div class="pull-right">
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#sendBatchModal">
                                <i class="icon-paper-plane"></i> {{ __('satisfaction.send_batch') }}
                            </button>
                        </div>
                    </div>
                    <table class="table table-striped table-bordered" id="surveysDataTable">
                        <thead>
                            <tr>
                                <th>{{ __('satisfaction.patient') }}</th>
                                <th>{{ __('satisfaction.doctor') }}</th>
                                <th>{{ __('satisfaction.date') }}</th>
                                <th>{{ __('satisfaction.rating') }}</th>
                                <th>{{ __('satisfaction.status.label') }}</th>
                                <th>{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 批量发送模态框 --}}
<div class="modal fade" id="sendBatchModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">{{ __('satisfaction.send_batch') }}</h4>
            </div>
            <form id="sendBatchForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('satisfaction.select_date') }}</label>
                        <input type="text" name="date" class="form-control datepicker" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('satisfaction.channel') }}</label>
                        <select name="channel" class="form-control" required>
                            <option value="sms">{{ __('satisfaction.channels.sms') }}</option>
                            <option value="wechat">{{ __('satisfaction.channels.wechat') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('common.send') }}</button>
                </div>
            </form>
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

    // 月度趋势图
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($monthlyTrend, 'month_label')) !!},
            datasets: [{
                label: '{{ __("satisfaction.avg_rating") }}',
                data: {!! json_encode(array_column($monthlyTrend, 'avg_rating')) !!},
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: 'NPS',
                data: {!! json_encode(array_column($monthlyTrend, 'nps')) !!},
                borderColor: '#4CAF50',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true, max: 5, position: 'left' },
                y1: { beginAtZero: false, min: -100, max: 100, position: 'right', grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // DataTable
    $('#surveysDataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ url("satisfaction-surveys/data") }}',
            data: function(d) {
                d.start_date = '{{ $startDate->format("Y-m-d") }}';
                d.end_date = '{{ $endDate->format("Y-m-d") }}';
            }
        },
        columns: [
            { data: 'patient_name', name: 'patient_name' },
            { data: 'doctor_name', name: 'doctor_name' },
            { data: 'survey_date_formatted', name: 'survey_date' },
            { data: 'ratings_display', name: 'overall_rating' },
            { data: 'status_badge', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });

    // 批量发送
    $('#sendBatchForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ url("satisfaction-surveys/send-batch") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(res) {
                $('#sendBatchModal').modal('hide');
                toastr.success(res.message);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __("common.error") }}');
            }
        });
    });
});
</script>
@endsection
