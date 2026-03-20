@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.doctor_performance_report'))
@section('table_id', 'payment-report')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('download-performance-report') }}" class="text-danger">
        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
    </a>
@endsection

{{-- ========================================================================
     Filter Area
     ======================================================================== --}}
@section('filter_area')
    <div class="filter-row">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('report.choose_doctor') }}</label>
                    <select class="form-control doctor_id" name="doctor_id">
                        @foreach($doctors as $row)
                            <option value="{{ $row->id }}">{{ $row->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('report.period') }}</label>
                    <select class="form-control" id="period_selector">
                        <option value="Today">{{ __('report.today') }}</option>
                        <option value="Yesterday">{{ __('report.yesterday') }}</option>
                        <option value="This week">{{ __('report.this_week') }}</option>
                        <option value="Last week">{{ __('report.last_week') }}</option>
                        <option value="This Month">{{ __('report.this_month') }}</option>
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
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.date') }}</th>
    <th>{{ __('report.patient_name') }}</th>
    <th>{{ __('report.procedures_cost') }}</th>
    <th>{{ __('report.overall_invoice_amount') }}</th>
    <th>{{ __('report.paid_amount') }}</th>
    <th>{{ __('report.outstanding_amount') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script>
window.DoctorPerformanceReportConfig = { ajaxUrl: '{{ url('/doctor-performance-report') }}' };
</script>
<script src="{{ asset('include_js/doctor_performance_report.js') }}?v={{ filemtime(public_path('include_js/doctor_performance_report.js')) }}"></script>
@endsection
