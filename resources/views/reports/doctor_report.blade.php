@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/doctor-report.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-user"></i>
                    <span class="caption-subject">{{ __('report.doctor_report') }}</span>
                </div>
            </div>
            <div class="portlet-body">

                {{-- Tab 导航 --}}
                <div class="report-tabs">
                    <ul class="nav nav-tabs" id="doctorReportTabs">
                        <li class="{{ $activeTab === 'performance' ? 'active' : '' }}">
                            <a href="#tab-performance" data-toggle="tab">{{ __('report.performance_tab') }}</a>
                        </li>
                        <li class="{{ $activeTab === 'workload' ? 'active' : '' }}">
                            <a href="#tab-workload" data-toggle="tab">{{ __('report.workload_tab') }}</a>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">
                    {{-- Tab 1: 收费统计 --}}
                    <div id="tab-performance" class="tab-pane {{ $activeTab === 'performance' ? 'active' : '' }}">
                        <div class="row" style="margin-bottom: 15px;">
                            @if($isDoctorUser)
                                <input type="hidden" id="perf_doctor_id" value="{{ $currentUserId }}">
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.doctor') }}</label>
                                    <p class="form-control-static">{{ Auth::user()->full_name }}</p>
                                </div>
                            @else
                            <div class="col-md-3">
                                <label class="control-label">{{ __('report.choose_doctor') }}</label>
                                <select class="form-control" id="perf_doctor_id">
                                    @foreach($doctors as $row)
                                        <option value="{{ $row->id }}">{{ $row->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-3">
                                <label class="control-label">{{ __('report.period') }}</label>
                                <select class="form-control" id="perf_period_selector">
                                    <option value="Today">{{ __('report.today') }}</option>
                                    <option value="Yesterday">{{ __('report.yesterday') }}</option>
                                    <option value="This week">{{ __('report.this_week') }}</option>
                                    <option value="Last week">{{ __('report.last_week') }}</option>
                                    <option value="This Month" selected>{{ __('report.this_month') }}</option>
                                    <option value="Last Month">{{ __('report.last_month') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">{{ __('common.start_date') }}</label>
                                <input type="text" class="form-control" id="perf_start_date">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">{{ __('common.end_date') }}</label>
                                <input type="text" class="form-control" id="perf_end_date">
                            </div>
                            <div class="col-md-2 text-right">
                                <label class="control-label">&nbsp;</label>
                                <div>
                                    <a href="{{ url('download-doctor-performance') }}" class="btn btn-default btn-sm">
                                        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
                                    </a>
                                    <button type="button" id="perf_search_btn" class="btn btn-primary btn-sm">{{ __('common.search') }}</button>
                                </div>
                            </div>
                        </div>
                        <table id="performanceTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('report.patient_name') }}</th>
                                    <th>{{ __('report.procedures_cost') }}</th>
                                    <th>{{ __('report.overall_invoice_amount') }}</th>
                                    <th>{{ __('report.paid_amount') }}</th>
                                    <th>{{ __('report.outstanding_amount') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- Tab 2: 工作量统计 --}}
                    <div id="tab-workload" class="tab-pane {{ $activeTab === 'workload' ? 'active' : '' }}">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-3">
                                <label class="control-label">{{ __('datetime.date_range.start_date') }}</label>
                                <input type="text" name="wl_start_date" id="wl_start_date" class="form-control datepicker"
                                       value="{{ isset($startDate) ? $startDate->format('Y-m-d') : '' }}"
                                       placeholder="{{ __('datetime.date_range.start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">{{ __('datetime.date_range.end_date') }}</label>
                                <input type="text" name="wl_end_date" id="wl_end_date" class="form-control datepicker"
                                       value="{{ isset($endDate) ? $endDate->format('Y-m-d') : '' }}"
                                       placeholder="{{ __('datetime.date_range.end_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">&nbsp;</label>
                                <div>
                                    <button type="button" id="wl_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                                </div>
                            </div>
                        </div>

                        {{-- 统计卡片 --}}
                        @if(isset($totalAppointments))
                        <div class="stat-cards">
                            <div class="stat-card highlight">
                                <div class="stat-value">{{ $totalAppointments }}</div>
                                <div class="stat-label">{{ __('report.total_appointments') }}</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">{{ $totalCompleted }}</div>
                                <div class="stat-label">{{ __('report.completed') }}</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">{{ $overallCompletionRate }}%</div>
                                <div class="stat-label">{{ __('report.completion_rate') }}</div>
                            </div>
                        </div>

                        {{-- 医生排名表 --}}
                        <div class="chart-container">
                            <div class="chart-title">{{ $isDoctorUser ? __('report.my_workload') : __('report.doctor_ranking') }}</div>
                            @if($doctorStats->isNotEmpty())
                            <table class="table table-striped table-bordered table-report">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('report.doctor_name') }}</th>
                                        <th class="text-center">{{ __('report.total_appointments_col') }}</th>
                                        <th class="text-center">{{ __('report.completed_col') }}</th>
                                        <th class="text-center">{{ __('report.cancelled') }}</th>
                                        <th class="text-center">{{ __('report.no_show_col') }}</th>
                                        <th class="text-center">{{ __('report.completion_rate_col') }}</th>
                                        <th class="text-center">{{ __('report.no_show_rate_col') }}</th>
                                        <th class="text-center">{{ __('report.daily_avg') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($doctorStats as $idx => $doc)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>{{ $doc->doctor_name }}</td>
                                        <td class="text-center">{{ $doc->total_appointments }}</td>
                                        <td class="text-center">{{ $doc->completed }}</td>
                                        <td class="text-center">{{ $doc->cancelled }}</td>
                                        <td class="text-center">{{ $doc->no_show }}</td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doc->completion_rate >= 80 ? 'good' : ($doc->completion_rate >= 60 ? 'warn' : 'bad') }}">{{ $doc->completion_rate }}%</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge-rate {{ $doc->no_show_rate <= 5 ? 'good' : ($doc->no_show_rate <= 15 ? 'warn' : 'bad') }}">{{ $doc->no_show_rate }}%</span>
                                        </td>
                                        <td class="text-center">{{ $doc->daily_avg }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                                <p class="text-muted text-center" style="padding: 20px;">{{ __('common.no_data_available') }}</p>
                            @endif
                        </div>

                        {{-- 每日工作量趋势 --}}
                        <div class="chart-container">
                            <div class="chart-title">{{ __('report.daily_workload_trend') }}</div>
                            <canvas id="dailyWorkloadChart" height="100"></canvas>
                        </div>
                        @else
                            <p class="text-muted text-center" style="padding: 40px;">{{ __('report.select_date_range_hint') }}</p>
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
    window.DoctorReportConfig = {
        locale:     '{{ app()->getLocale() }}',
        dataUrl:    '{{ url("doctor-report") }}',
        dailyTrend: {!! isset($dailyTrend) ? json_encode($dailyTrend) : 'null' !!}
    };
</script>
<script src="{{ asset('include_js/doctor_report.js') }}?v={{ filemtime(public_path('include_js/doctor_report.js')) }}"></script>
@endsection
