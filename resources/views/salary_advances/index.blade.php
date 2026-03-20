@extends('layouts.list-page')

@section('page_title', __('salary_advances.page_title'))

@section('table_id', 'salary-advances-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('salary_advances.id') }}</th>
    <th>{{ __('salary_advances.employee') }}</th>
    <th>{{ __('salary_advances.classification') }}</th>
    <th>{{ __('salary_advances.payslip_month') }}</th>
    <th>{{ __('salary_advances.amount') }}</th>
    <th>{{ __('salary_advances.payment_method') }}</th>
    <th>{{ __('salary_advances.payment_date') }}</th>
    <th>{{ __('salary_advances.added_by') }}</th>
    <th>{{ __('salary_advances.edit') }}</th>
    <th>{{ __('salary_advances.delete') }}</th>
@endsection

@section('modals')
@include('salary_advances.create')
@endsection

@section('page_js')
<script>
    window.SalaryAdvancesConfig = {
        indexUrl: "{{ url('/salary-advances/') }}",
        locale: "{{ app()->getLocale() }}",
        translations: {
            'salary_advances': @json(__('salary_advances')),
            'common': @json(__('common'))
        }
    };
</script>
<script src="{{ asset('include_js/salary_advances_index.js') }}?v={{ filemtime(public_path('include_js/salary_advances_index.js')) }}"></script>
@endsection
