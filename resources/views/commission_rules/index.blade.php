@extends('layouts.list-page')

@section('page_title', __('commission_rules.title'))
@section('table_id', 'commission_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('commission_rules.rule_name') }}</th>
    <th>{{ __('commission_rules.mode') }}</th>
    <th>{{ __('commission_rules.rate') }}</th>
    <th>{{ __('commission_rules.service') }}</th>
    <th>{{ __('commission_rules.branch') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('commission_rules.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'commission_rules': @json(__('commission_rules'))
    });

    window.CommissionRulesConfig = {
        urls: {
            commissionRules: "{{ url('/commission-rules') }}"
        }
    };
</script>
<script src="{{ asset('include_js/commission_rules_index.js') }}?v={{ filemtime(public_path('include_js/commission_rules_index.js')) }}"></script>
@endsection
