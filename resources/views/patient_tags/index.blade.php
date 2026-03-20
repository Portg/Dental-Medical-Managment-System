{{--
    Patient Tags List Page
    Extends the list-page base template
--}}
@extends('layouts.list-page')

{{-- ========================================================================
     Required Sections
     ======================================================================== --}}

@section('page_title')
    {{ __('patient_tags.patient_tags') }}
@endsection

@section('table_id', 'tags-table')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('patient_tags.tag') }}</th>
    <th>{{ __('patient_tags.color') }}</th>
    <th>{{ __('patient_tags.patients_count') }}</th>
    <th>{{ __('patient_tags.sort_order') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

{{-- ========================================================================
     Filter Area
     ======================================================================== --}}
@section('filter_primary')
    <div class="col-md-4">
        <div class="filter-label">{{ __('common.search') }}</div>
        <div class="search-input-wrapper">
            <i class="fa fa-search search-icon"></i>
            <input type="text" id="quickSearch" class="form-control"
                   placeholder="{{ __('patient_tags.search_tags') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="filter-label">{{ __('common.status') }}</div>
        <select id="filter_status" class="form-control">
            <option value="">{{ __('common.all') }}</option>
            <option value="1">{{ __('common.active') }}</option>
            <option value="0">{{ __('common.inactive') }}</option>
        </select>
    </div>
@endsection

{{-- ========================================================================
     Empty State
     ======================================================================== --}}
@section('empty_icon', 'fa-tags')

@section('empty_title')
    {{ __('patient_tags.no_tags_found') }}
@endsection

@section('empty_desc')
    {{ __('patient_tags.click_add_to_start') }}
@endsection

@section('empty_action')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

{{-- ========================================================================
     Modal Dialogs
     ======================================================================== --}}
@section('modals')
    @include('patient_tags.create')
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
    <script>
        LanguageManager.loadAllFromPHP({
            'patient_tags': @json(__('patient_tags')),
            'common': @json(__('common'))
        });
        window.PatientTagsConfig = {
            ajaxUrl: "{{ url('/patient-tags/') }}",
            baseUrl: "{{ url('/patient-tags') }}"
        };
    </script>
    <script src="{{ asset('include_js/patient_tags_index.js') }}?v={{ filemtime(public_path('include_js/patient_tags_index.js')) }}"></script>
@endsection
