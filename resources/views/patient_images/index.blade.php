@extends('layouts.list-page')

@section('page_title', __('patient_images.page_title'))

@section('table_id', 'patient_images_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{__('common.add_new')}}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('patient_images.image_no') }}</th>
    <th>{{ __('patient_images.title') }}</th>
    <th>{{ __('patient_images.patient') }}</th>
    <th>{{ __('patient_images.image_type') }}</th>
    <th>{{ __('patient_images.image_date') }}</th>
    <th>{{ __('common.view') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('empty_icon', 'fa-image')
@section('empty_title', __('patient_images.no_images_found'))

@section('modals')
    @include('patient_images.create')
    @include('patient_images.view')
@endsection

@section('page_js')
<script>
    var patients = @json($patients);

    // Load translations for JavaScript
    LanguageManager.loadAllFromPHP({
        'patient_images': @json(__('patient_images')),
        'messages': @json(__('messages'))
    });

    // Override createRecord to open add image modal
    function createRecord() {
        addImage();
    }
</script>
<script src="{{ asset('include_js/patient_images.js') }}"></script>
@endsection
