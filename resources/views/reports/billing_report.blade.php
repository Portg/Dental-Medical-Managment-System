@extends('layouts.list-page')

@section('page_title', __('report.billing_report'))
@section('table_id', 'billingTable')

@section('header_actions')
    <span id="export-btn-area">
        <a href="{{ url('billing-report/export-payments') }}" class="text-danger" id="export-payments-btn">
            <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
        </a>
    </span>
@endsection

@section('filter_area')
    {{-- Tab 导航 --}}
    <ul class="nav nav-tabs" id="billingReportTabs" style="margin-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
        <li class="{{ $activeTab === 'payments' ? 'active' : '' }}" id="tab-payments-li">
            <a href="#" data-tab="payments" class="billing-tab-btn" style="border: none; border-bottom: 3px solid transparent; padding: 8px 16px; font-weight: 500; {{ $activeTab === 'payments' ? 'color:#1A237E; border-bottom-color:#1A237E;' : 'color:#666;' }}">{{ __('report.payments_tab') }}</a>
        </li>
        <li class="{{ $activeTab === 'procedures' ? 'active' : '' }}" id="tab-procedures-li">
            <a href="#" data-tab="procedures" class="billing-tab-btn" style="border: none; border-bottom: 3px solid transparent; padding: 8px 16px; font-weight: 500; {{ $activeTab === 'procedures' ? 'color:#1A237E; border-bottom-color:#1A237E;' : 'color:#666;' }}">{{ __('report.procedures_tab') }}</a>
        </li>
    </ul>

    <div id="payments-filters" style="{{ $activeTab !== 'payments' ? 'display:none;' : '' }}">
        <div class="row filter-row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('report.period') }}</label>
                    <select class="form-control" id="period_selector">
                        <option value="Today">{{ __('report.today') }}</option>
                        <option value="This Month" selected>{{ __('report.this_month') }}</option>
                        <option value="Last Month">{{ __('report.last_month') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">{{ __('common.start_date') }}</label>
                    <input type="text" class="form-control start_date">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">{{ __('common.end_date') }}</label>
                    <input type="text" class="form-control end_date">
                </div>
            </div>
            <div class="col-md-2 text-right filter-actions">
                <label class="control-label">&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                    <button type="button" id="customFilterBtn" class="btn btn-primary">{{ __('common.search') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div id="procedures-filters" style="{{ $activeTab !== 'procedures' ? 'display:none;' : '' }}">
        <div class="row filter-row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('report.period') }}</label>
                    <select class="form-control" id="proc_period_selector">
                        <option value="Today">{{ __('report.today') }}</option>
                        <option value="This Month" selected>{{ __('report.this_month') }}</option>
                        <option value="Last Month">{{ __('report.last_month') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">{{ __('common.start_date') }}</label>
                    <input type="text" class="form-control" id="proc_start_date">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="control-label">{{ __('common.end_date') }}</label>
                    <input type="text" class="form-control" id="proc_end_date">
                </div>
            </div>
            <div class="col-md-2 text-right filter-actions">
                <label class="control-label">&nbsp;</label>
                <div>
                    <button type="button" id="proc_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('table_headers')
    {{-- Payments headers (default) --}}
    <th id="th-invoice-no">{{ __('report.invoice_no') }}</th>
    <th id="th-date">{{ __('report.invoice_date') }}</th>
    <th id="th-patient">{{ __('report.patient_name') }}</th>
    <th id="th-amount">{{ __('report.paid_amount') }}</th>
    <th id="th-method">{{ __('report.payment_method') }}</th>
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('report')), 'report');
    window.BillingReportConfig = {
        activeTab:          '{{ $activeTab }}',
        dataUrl:            '{{ url("billing-report") }}',
        exportPaymentsUrl:  '{{ url("export-billing-payments") }}',
        exportProceduresUrl:'{{ url("export-billing-procedures") }}'
    };
</script>
<script src="{{ asset('include_js/billing_report.js') }}?v={{ filemtime(public_path('include_js/billing_report.js')) }}"></script>

{{-- Procedures table (hidden initially unless tab=procedures) --}}
<div id="procedureTableWrapper" style="{{ $activeTab !== 'procedures' ? 'display:none;' : '' }}">
    <table id="procedureTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('report.procedure_name') }}</th>
                <th>{{ __('report.procedure_income') }}</th>
            </tr>
        </thead>
    </table>
</div>
@endsection
