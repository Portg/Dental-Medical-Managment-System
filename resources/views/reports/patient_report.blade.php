@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/patient-report.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-people"></i>
                    <span class="caption-subject">{{ __('report.patient_report_title') }}</span>
                </div>
                <div class="actions">
                    <form class="form-inline" method="GET" id="dateFilterForm" style="display: inline-flex; gap: 10px;">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
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

                {{-- Tab 导航 --}}
                <div class="report-tabs">
                    <ul class="nav nav-tabs" id="patientReportTabs">
                        <li class="{{ $activeTab === 'source' ? 'active' : '' }}">
                            <a href="?tab=source&start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}">{{ __('report.source_analysis_tab') }}</a>
                        </li>
                        <li class="{{ $activeTab === 'demographics' ? 'active' : '' }}">
                            <a href="?tab=demographics&start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}">{{ __('report.demographics_tab') }}</a>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">
                    {{-- Tab 1: 来源分析 --}}
                    <div id="tab-source" class="tab-pane {{ $activeTab === 'source' ? 'active' : '' }}">
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

                        <div class="row">
                            <div class="col-md-8">
                                <div class="chart-container">
                                    <div class="chart-title">{{ __('report.source_distribution') }}</div>
                                    @foreach($sourceAnalysis as $source)
                                        <div class="progress-bar-wrapper">
                                            <div class="label-row">
                                                <span>
                                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $source['color'] }};margin-right:5px;"></span>
                                                    {{ $source['name'] }}
                                                </span>
                                                <span>
                                                    {{ $source['patient_count'] }} {{ __('report.patients') }} ({{ $source['percentage'] }}%)
                                                    @php $rateClass = $source['conversion_rate'] >= 70 ? 'high' : ($source['conversion_rate'] >= 40 ? 'medium' : 'low'); @endphp
                                                    <span class="conversion-rate {{ $rateClass }}">{{ __('report.conversion') }}: {{ $source['conversion_rate'] }}%</span>
                                                </span>
                                            </div>
                                            <div class="bar">
                                                <div class="bar-fill" style="width:{{ $source['percentage'] }}%;background:{{ $source['color'] }};">
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
                                            <td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $source['color'] }};margin-right:5px;"></span>{{ $source['name'] }}</td>
                                            <td>{{ $source['patient_count'] }}</td>
                                            <td>{{ $source['percentage'] }}%</td>
                                            <td>{{ $source['converted_count'] }}</td>
                                            <td>
                                                @php $rateClass = $source['conversion_rate'] >= 70 ? 'high' : ($source['conversion_rate'] >= 40 ? 'medium' : 'low'); @endphp
                                                <span class="conversion-rate {{ $rateClass }}">{{ $source['conversion_rate'] }}%</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab 2: 人口统计 (只在 demographics tab 加载数据) --}}
                    <div id="tab-demographics" class="tab-pane {{ $activeTab === 'demographics' ? 'active' : '' }}">
                        @if($activeTab === 'demographics')
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
                            <div class="col-md-5">
                                <div class="chart-container">
                                    <div class="chart-title">{{ __('report.age_distribution') }}</div>
                                    <canvas id="ageChart" height="200"></canvas>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="chart-container">
                                    <div class="chart-title">{{ __('report.gender_distribution') }}</div>
                                    <canvas id="genderChart" height="200"></canvas>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="chart-container">
                                    <div class="chart-title">{{ __('report.source_distribution') }}</div>
                                    <canvas id="sourceDistChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="chart-container">
                                    <div class="chart-title">{{ __('report.new_patient_trend') }}</div>
                                    <canvas id="newPatientTrendChart" height="80"></canvas>
                                </div>
                            </div>
                        </div>

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
                        @endif
                    </div>
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
    window.PatientReportConfig = {
        locale:             '{{ app()->getLocale() }}',
        sourceAnalysis:     {!! $activeTab === 'source' ? json_encode(array_map(fn($r) => ['name' => $r['name'], 'patient_count' => $r['patient_count'], 'color' => $r['color']], $sourceAnalysis)) : 'null' !!},
        ageDistribution:    {!! $activeTab === 'demographics' ? json_encode($ageDistribution)    : 'null' !!},
        genderDistribution: {!! $activeTab === 'demographics' ? json_encode($genderDistribution) : 'null' !!},
        sourceDistribution: {!! $activeTab === 'demographics' ? json_encode($sourceDistribution) : 'null' !!},
        newPatientTrend:    {!! $activeTab === 'demographics' ? json_encode($newPatientTrend)    : 'null' !!}
    };
</script>
<script src="{{ asset('include_js/patient_report.js') }}?v={{ filemtime(public_path('include_js/patient_report.js')) }}"></script>
@endsection
