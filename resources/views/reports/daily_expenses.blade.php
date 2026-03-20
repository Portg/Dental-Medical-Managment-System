@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.todays_expense_report'))
@section('table_id', 'sample_1')

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.time') }}</th>
    <th>{{ __('common.name') }}</th>
    <th>{{ __('common.amount') }}</th>
    <th>{{ __('report.added_by') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script>
window.DailyExpensesConfig = { ajaxUrl: '{{ url('/todays-expenses/') }}' };
</script>
<script src="{{ asset('include_js/daily_expenses_report.js') }}?v={{ filemtime(public_path('include_js/daily_expenses_report.js')) }}"></script>
@endsection
