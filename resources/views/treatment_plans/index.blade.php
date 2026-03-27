@extends('layouts.list-page')

@section('page_title', __('medical_cases.treatment_plans'))

@section('table_id', 'treatment_plans_table')

{{-- Header Actions --}}
@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        <i class="fa fa-plus"></i> {{ __('medical_cases.add_treatment_plan') }}
    </button>
@endsection

{{-- Primary Filters --}}
@section('filter_primary')
    <div class="col-md-4">
        <div class="filter-label">{{ __('common.search') }}</div>
        <div class="search-input-wrapper">
            <i class="fa fa-search search-icon"></i>
            <input type="text" class="form-control" id="search-input"
                   placeholder="{{ __('medical_cases.search_treatment_plan_placeholder') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="filter-label">{{ __('medical_cases.status') }}</div>
        <select class="form-control" id="filter-status">
            <option value="">{{ __('common.all') }}</option>
            <option value="Planned">{{ __('medical_cases.plan_status_planned') }}</option>
            <option value="In Progress">{{ __('medical_cases.plan_status_in_progress') }}</option>
            <option value="Completed">{{ __('medical_cases.plan_status_completed') }}</option>
            <option value="Cancelled">{{ __('medical_cases.plan_status_cancelled') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <div class="filter-label">{{ __('medical_cases.priority') }}</div>
        <select class="form-control" id="filter-priority">
            <option value="">{{ __('common.all') }}</option>
            <option value="Low">{{ __('medical_cases.priority_low') }}</option>
            <option value="Medium">{{ __('medical_cases.priority_medium') }}</option>
            <option value="High">{{ __('medical_cases.priority_high') }}</option>
            <option value="Urgent">{{ __('medical_cases.priority_urgent') }}</option>
        </select>
    </div>
@endsection

@section('table_headers')
    <th>#</th>
    <th>{{ __('patient.patient_no') }}</th>
    <th>{{ __('patient.patient_name') }}</th>
    <th>{{ __('medical_cases.plan_name') }}</th>
    <th>{{ __('medical_cases.status') }}</th>
    <th>{{ __('medical_cases.priority') }}</th>
    <th>{{ __('medical_cases.estimated_cost') }}</th>
    <th>{{ __('common.created_at') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    {{-- View Treatment Plan Modal --}}
    @include('medical_cases.treatment_plans.view')

    {{-- Create/Edit Treatment Plan Modal --}}
    @include('medical_cases.treatment_plans.create')
@endsection

@section('page_js')
<script>
window.TreatmentPlansConfig = {
    ajaxUrl: "{{ url('treatment-plans') }}",
    csrfToken: "{{ csrf_token() }}"
};
LanguageManager.loadFromPHP(@json(__('medical_cases')), 'medical_cases');
</script>
<script src="{{ asset('include_js/treatment_plans_index.js') }}?v={{ filemtime(public_path('include_js/treatment_plans_index.js')) }}"></script>
@endsection
