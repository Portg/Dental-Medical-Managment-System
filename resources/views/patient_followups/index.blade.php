@extends('layouts.list-page')

@section('page_title', __('patient_followups.page_title'))

@section('table_id', 'patient_followups_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{__('common.add_new')}}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('patient_followups.followup_no') }}</th>
    <th>{{ __('patient_followups.patient') }}</th>
    <th>{{ __('patient_followups.type') }}</th>
    <th>{{ __('patient_followups.scheduled_date') }}</th>
    <th>{{ __('patient_followups.purpose') }}</th>
    <th>{{ __('patient_followups.status') }}</th>
    <th></th>
    <th>{{ __('common.view') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
    <th></th>
@endsection

@section('empty_icon', 'fa-calendar-check-o')
@section('empty_title', __('patient_followups.no_followups_found'))

@section('modals')
    @include('patient_followups.create')
    @include('patient_followups.view')
    @include('patient_followups.complete')
@endsection

@section('page_js')
<script>
    var patients = @json($patients);

    // Load translations for JavaScript
    LanguageManager.loadAllFromPHP({
        'common': @json(__('common')),
        'patient_followups': @json(__('patient_followups')),
        'messages': @json(__('messages'))
    });

    // Override createRecord to open add followup modal
    function createRecord() {
        addFollowup();
    }
</script>
<script src="{{ asset('include_js/patient_followups.js') }}"></script>
@endsection
