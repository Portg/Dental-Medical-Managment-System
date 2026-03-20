@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/patient-demographics.css') }}">
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
<script>
LanguageManager.loadFromPHP(@json(__('report')), 'report');
window.PatientDemographicsConfig = {
    ageDistribution:    @json($ageDistribution),
    genderDistribution: @json($genderDistribution),
    sourceDistribution: @json($sourceDistribution),
    newPatientTrend:    @json($newPatientTrend)
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/patient_demographics_report.js') }}?v={{ filemtime(public_path('include_js/patient_demographics_report.js')) }}"></script>
@endsection
