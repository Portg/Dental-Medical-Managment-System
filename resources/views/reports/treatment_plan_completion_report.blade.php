@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/treatment-plan-completion.css') }}">
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
<script>
LanguageManager.loadFromPHP(@json(__('report')), 'report');
window.TreatmentPlanCompletionConfig = {
    locale:       '{{ app()->getLocale() }}',
    monthlyTrend: @json($monthlyTrend),
    byDoctor:     @json($byDoctor)
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/treatment_plan_completion_report.js') }}?v={{ filemtime(public_path('include_js/treatment_plan_completion_report.js')) }}"></script>
@endsection
