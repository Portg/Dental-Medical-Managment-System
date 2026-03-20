@extends('layouts.list-page')

@section('page_title', __('employee_contracts.title'))

@section('table_id', 'contracts-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('employee_contracts.employee') }}</th>
    <th>{{ __('employee_contracts.contract_type') }}</th>
    <th>{{ __('employee_contracts.length') }}</th>
    <th>{{ __('employee_contracts.start') }}</th>
    <th>{{ __('employee_contracts.end') }}</th>
    <th>{{ __('employee_contracts.payroll_type') }}</th>
    <th>{{ __('employee_contracts.salary_commission') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('employee_contracts.create')
@endsection

@section('page_js')
<script>
    window.EmployeeContractsConfig = {
        indexUrl: "{{ url('/employee-contracts/') }}",
        locale: "{{ app()->getLocale() }}",
        translations: {
            'employee_contracts': @json(__('employee_contracts')),
            'common': @json(__('common'))
        }
    };
</script>
<script src="{{ asset('include_js/employee_contracts_index.js') }}?v={{ filemtime(public_path('include_js/employee_contracts_index.js')) }}"></script>
@endsection
