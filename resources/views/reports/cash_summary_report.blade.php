@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/cash-summary.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-wallet"></i>
                    <span class="caption-subject">{{ __('report.cash_summary_report') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('export-cash-summary') }}?start_date={{ $startDate }}&end_date={{ $endDate }}&tab={{ $activeTab }}"
                       class="btn btn-sm btn-default">
                        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 日期筛选 --}}
                <form method="GET" class="filter-row">
                    <div class="row">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="col-md-3">
                            <label class="control-label">{{ __('report.start_date') }}</label>
                            <input type="text" name="start_date" class="form-control datepicker" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">{{ __('report.end_date') }}</label>
                            <input type="text" name="end_date" class="form-control datepicker" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">{{ __('common.search') }}</button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Tab 导航 --}}
                <ul class="nav nav-tabs" id="cashSummaryTabs">
                    <li class="{{ $activeTab === 'payment_method' ? 'active' : '' }}">
                        <a href="{{ url('cash-summary-report') }}?tab=payment_method&start_date={{ $startDate }}&end_date={{ $endDate }}">
                            {{ __('report.by_payment_method') }}
                        </a>
                    </li>
                    <li class="{{ $activeTab === 'collector' ? 'active' : '' }}">
                        <a href="{{ url('cash-summary-report') }}?tab=collector&start_date={{ $startDate }}&end_date={{ $endDate }}">
                            {{ __('report.by_collector') }}
                        </a>
                    </li>
                    <li class="{{ $activeTab === 'date' ? 'active' : '' }}">
                        <a href="{{ url('cash-summary-report') }}?tab=date&start_date={{ $startDate }}&end_date={{ $endDate }}">
                            {{ __('report.by_date') }}
                        </a>
                    </li>
                    <li class="{{ $activeTab === 'doctor' ? 'active' : '' }}">
                        <a href="{{ url('cash-summary-report') }}?tab=doctor&start_date={{ $startDate }}&end_date={{ $endDate }}">
                            {{ __('report.by_doctor') }}
                        </a>
                    </li>
                    <li class="{{ $activeTab === 'service_category' ? 'active' : '' }}">
                        <a href="{{ url('cash-summary-report') }}?tab=service_category&start_date={{ $startDate }}&end_date={{ $endDate }}">
                            {{ __('report.by_service_category') }}
                        </a>
                    </li>
                </ul>

                <div style="margin-top: 20px;">
                    @php
                        $totalBills  = $rows->sum('bill_count');
                        $totalAmount = $rows->sum('total_amount');
                    @endphp

                    {{-- 汇总卡片 --}}
                    <div class="stat-cards">
                        <div class="stat-card highlight">
                            <div class="stat-value">{{ $rows->count() }}</div>
                            <div class="stat-label">{{ __('report.group_count') }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">{{ $totalBills }}</div>
                            <div class="stat-label">{{ __('report.total_bills') }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">¥{{ number_format($totalAmount, 0) }}</div>
                            <div class="stat-label">{{ __('report.total_income') }}</div>
                        </div>
                    </div>

                    {{-- 汇总表 --}}
                    <table class="table table-striped table-bordered summary-table">
                        <thead>
                            <tr>
                                <th>{{ $label_col }}</th>
                                <th class="text-center">{{ __('report.bill_count') }}</th>
                                <th class="text-right">{{ __('report.total_amount') }}</th>
                                <th class="text-right">{{ __('report.percentage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row->label }}</td>
                                <td class="text-center">{{ $row->bill_count }}</td>
                                <td class="amount">¥{{ number_format($row->total_amount, 2) }}</td>
                                <td class="text-right">
                                    {{ $totalAmount > 0 ? number_format($row->total_amount / $totalAmount * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>{{ __('common.total') }}</td>
                                <td class="text-center">{{ $totalBills }}</td>
                                <td class="amount">¥{{ number_format($totalAmount, 2) }}</td>
                                <td class="text-right">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    $('.datepicker').datepicker({ language: '{{ app()->getLocale() }}', format: 'yyyy-mm-dd', autoclose: true });
});
</script>
@endsection
