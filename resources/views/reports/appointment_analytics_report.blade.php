@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/appointment-analytics.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-calendar"></i>
                    <span class="caption-subject">{{ __('report.appointment_analytics') }}</span>
                </div>
                <div class="actions">
                    <form class="form-inline" method="GET" style="display: inline-flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="start_date" class="form-control input-sm datepicker"
                               value="{{ $startDate->format('Y-m-d') }}" placeholder="{{ __('datetime.date_range.start_date') }}">
                        <span>{{ __('datetime.date_range.to') }}</span>
                        <input type="text" name="end_date" class="form-control input-sm datepicker"
                               value="{{ $endDate->format('Y-m-d') }}" placeholder="{{ __('datetime.date_range.end_date') }}">

                        {{-- Patient source filter --}}
                        <select id="filter-source" name="source_id" class="form-control input-sm filter-select2">
                            <option value="">{{ __('report.all_sources') }}</option>
                            @foreach($sources as $src)
                                <option value="{{ $src->id }}" {{ $selectedSourceId == $src->id ? 'selected' : '' }}>
                                    {{ $src->name }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Patient tag filter (multi-select) --}}
                        <select id="filter-tags" name="tag_ids[]" class="form-control input-sm filter-select2" multiple>
                            @foreach($patientTags as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, (array) $selectedTagIds) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="icon-magnifier"></i> {{ __('common.search') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 统计卡片 --}}
                <div class="stat-cards">
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $totalAppointments }}</div>
                        <div class="stat-label">{{ __('report.total_appointments') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $completionRate }}%</div>
                        <div class="stat-label">{{ __('report.completion_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $cancellationRate }}%</div>
                        <div class="stat-label">{{ __('report.cancellation_rate') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $noShowRate }}%</div>
                        <div class="stat-label">{{ __('report.no_show_rate') }}</div>
                    </div>
                </div>

                <div class="row">
                    {{-- 每日预约趋势 --}}
                    <div class="col-md-8">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.daily_appointment_trend') }}</div>
                            <canvas id="dailyTrendChart" height="120"></canvas>
                        </div>
                    </div>

                    {{-- 就诊高峰时段 --}}
                    <div class="col-md-4">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.peak_hours_distribution') }}</div>
                            <canvas id="peakHoursChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- 医生预约统计 --}}
                    <div class="col-md-7">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.by_doctor_breakdown') }}</div>
                            @if($doctorStats->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>{{ __('report.doctor_name') }}</th>
                                        <th class="text-center">{{ __('report.total') }}</th>
                                        <th class="text-center">{{ __('report.completed') }}</th>
                                        <th class="text-center">{{ __('report.cancelled') }}</th>
                                        <th class="text-center">{{ __('report.no_show') }}</th>
                                        <th class="text-center">{{ __('report.completion_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($doctorStats as $doctor)
                                    <tr>
                                        <td>{{ $doctor->doctor_name }}</td>
                                        <td class="text-center">{{ $doctor->total }}</td>
                                        <td class="text-center">{{ $doctor->completed }}</td>
                                        <td class="text-center">{{ $doctor->cancelled }}</td>
                                        <td class="text-center">{{ $doctor->no_show }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doctor->completion_rate >= 80 ? 'good' : ($doctor->completion_rate >= 60 ? 'warn' : 'bad') }}">
                                                {{ $doctor->completion_rate }}%
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

                    {{-- 预约来源分布 --}}
                    <div class="col-md-5">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.appointment_source_distribution') }}</div>
                            <canvas id="sourceChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                {{-- 诊椅使用率 --}}
                @if(count($chairUtilization) > 0)
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.chair_utilization') }}</div>
                            <canvas id="chairChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('report')), 'report');
window.AppointmentAnalyticsConfig = {
    locale:           '{{ app()->getLocale() }}',
    dailyTrendDates:  @json(array_column($dailyTrend, 'date')),
    dailyTrendCounts: @json(array_column($dailyTrend, 'count')),
    peakHoursData:    @json($peakHours),
    sourceData:       @json($sourceDistribution),
    chairData:        @json($chairUtilization)
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ asset('include_js/appointment_analytics.js') }}?v={{ filemtime(public_path('include_js/appointment_analytics.js')) }}"></script>
@endsection
