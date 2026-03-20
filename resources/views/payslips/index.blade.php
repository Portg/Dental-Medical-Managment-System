@extends('layouts.list-page')

@section('page_title', __('payslips.page_title'))
@section('table_id', 'payslips-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('payslips.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('payslips.id') }}</th>
    <th>{{ __('payslips.employee') }}</th>
    <th>{{ __('payslips.month') }}</th>
    <th>{{ __('payslips.gross_commission') }}</th>
    <th>{{ __('payslips.allowance') }}</th>
    <th>{{ __('payslips.deductions') }}</th>
    <th>{{ __('payslips.paid') }}</th>
    <th>{{ __('payslips.outstanding') }}</th>
    <th>{{ __('payslips.added_by') }}</th>
    <th>{{ __('payslips.action') }}</th>
@endsection

@section('modals')
    @include('payslips.create')
@endsection

@section('page_js')
    <script>
        LanguageManager.loadAllFromPHP({
            'payslips': @json(__('payslips'))
        });
        window.PayslipsConfig = {
            ajaxUrl: "{{ url('/payslips/') }}",
            locale: "{{ app()->getLocale() }}"
        };
    </script>
    <script src="{{ asset('include_js/payslips_index.js') }}?v={{ filemtime(public_path('include_js/payslips_index.js')) }}"></script>
@endsection
