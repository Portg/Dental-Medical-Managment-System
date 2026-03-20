@extends('layouts.list-page')

@section('page_title', __('claim_rates.title'))

@section('table_id', 'claim-rates-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('claim_rates.date') }}</th>
    <th>{{ __('claim_rates.surname') }}</th>
    <th>{{ __('claim_rates.other_name') }}</th>
    <th>{{ __('claim_rates.cash_rate') }}</th>
    <th>{{ __('claim_rates.insurance_rate') }}</th>
    <th>{{ __('claim_rates.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('claim_rates.create')
@include('claim_rates.new_claim')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'claim_rates': @json(__('claim_rates'))
    });

    window.ClaimRatesConfig = {
        baseUrl: "{{ url('/claim-rates') }}",
        indexUrl: "{{ url('/claim-rates/') }}",
        locale: "{{ app()->getLocale() }}"
    };
</script>
<script src="{{ asset('include_js/claim_rates_index.js') }}?v={{ filemtime(public_path('include_js/claim_rates_index.js')) }}"></script>
@endsection
