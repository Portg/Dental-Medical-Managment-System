@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.receivables_report'))
@section('table_id', 'payment-report')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('debtors-export') }}" class="text-danger">
        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
    </a>
@endsection

{{-- ========================================================================
     Filter Area
     ======================================================================== --}}
@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-4">
            <div class="form-group">
                <label class="control-label">{{ __('report.period') }}</label>
                <select class="form-control" id="period_selector">
                    <option value="Today">{{ __('report.today') }}</option>
                    <option value="Yesterday">{{ __('report.yesterday') }}</option>
                    <option value="This week">{{ __('report.this_week') }}</option>
                    <option value="Last week">{{ __('report.last_week') }}</option>
                    <option value="This Month" selected>{{ __('report.this_month') }}</option>
                    <option value="Last Month">{{ __('report.last_month') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="control-label">{{ __('common.start_date') }}</label>
                <input type="text" class="form-control start_date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="control-label">{{ __('common.end_date') }}</label>
                <input type="text" class="form-control end_date">
            </div>
        </div>
        <div class="col-md-2 text-right filter-actions">
            <div class="form-group">
                <div>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                    <button type="button" id="customFilterBtn" class="btn btn-primary">{{ __('common.search') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('report.invoice_no') }}</th>
    <th>{{ __('report.invoice_date') }}</th>
    <th>{{ __('report.last_name') }}</th>
    <th>{{ __('report.first_name') }}</th>
    <th>{{ __('report.phone_no') }}</th>
    <th>{{ __('report.invoice_amount') }}</th>
    <th>{{ __('report.paid_amount') }}</th>
    <th>{{ __('report.outstanding_balance') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script>
window.DebtorsReportConfig = { ajaxUrl: '{{ url('/debtors') }}' };
</script>
<script src="{{ asset('include_js/debtors_report.js') }}?v={{ filemtime(public_path('include_js/debtors_report.js')) }}"></script>
@endsection
