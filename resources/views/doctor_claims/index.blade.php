@extends('layouts.list-page')

@section('page_title', __('doctor_claims.title'))
@section('table_id', 'doctor_claims_table')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('doctor_claims.date') }}</th>
    <th>{{ __('doctor_claims.patient') }}</th>
    <th>{{ __('doctor_claims.doctor') }}</th>
    <th>{{ __('doctor_claims.treatment_amount') }}</th>
    <th>{{ __('doctor_claims.insurance_claim') }}</th>
    <th>{{ __('doctor_claims.cash_claim') }}</th>
    <th>{{ __('doctor_claims.total_claim_amount') }}</th>
    <th>{{ __('doctor_claims.payment_balance') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('doctor_claims.create')
    @include('doctor_claims.payments.create')
@endsection

@section('page_js')
<script>
    LanguageManager.loadFromPHP(@json(__('doctor_claims')), 'doctor_claims');
    window.DoctorClaimsConfig = {
        indexUrl: "{{ url('/doctor-claims/') }}"
    };
</script>
<script src="{{ asset('include_js/doctor_claims_index.js') }}?v={{ filemtime(public_path('include_js/doctor_claims_index.js')) }}"></script>
@endsection
