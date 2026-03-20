@extends('layouts.list-page')

@section('page_title', __('self_accounts.accounting_manager_self_accounts'))
@section('table_id', 'self-accounts-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('self_accounts.account_no') }}</th>
    <th>{{ __('self_accounts.account_name') }}</th>
    <th>{{ __('self_accounts.phone_no') }}</th>
    <th>{{ __('common.email') }}</th>
    <th>{{ __('self_accounts.account_balance') }}</th>
    <th>{{ __('self_accounts.added_by') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('self_accounts.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('self_accounts')), 'self_accounts');
    window.SelfAccountsConfig = { baseUrl: "{{ url('/self-accounts') }}" };
</script>
<script src="{{ asset('include_js/self_accounts_index.js') }}?v={{ filemtime(public_path('include_js/self_accounts_index.js')) }}"></script>
@endsection
