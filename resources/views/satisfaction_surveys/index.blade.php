@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/satisfaction-surveys.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-emoticon-smile"></i>
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
<div class="modal fade modal-form" id="sendBatchModal" tabindex="-1" role="dialog">
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
    LanguageManager.loadFromPHP(@json(__('satisfaction')), 'satisfaction');
    window.SatisfactionSurveysConfig = {
        trendLabels: {!! json_encode(array_column($monthlyTrend, 'month_label')) !!},
        trendRatings: {!! json_encode(array_column($monthlyTrend, 'avg_rating')) !!},
        trendNps: {!! json_encode(array_column($monthlyTrend, 'nps')) !!},
        dataUrl: "{{ url('satisfaction-surveys/data') }}",
        sendBatchUrl: "{{ url('satisfaction-surveys/send-batch') }}",
        startDate: "{{ $startDate->format('Y-m-d') }}",
        endDate: "{{ $endDate->format('Y-m-d') }}"
    };
</script>
<script src="{{ asset('include_js/satisfaction_surveys_index.js') }}?v={{ filemtime(public_path('include_js/satisfaction_surveys_index.js')) }}" type="text/javascript"></script>
@endsection
