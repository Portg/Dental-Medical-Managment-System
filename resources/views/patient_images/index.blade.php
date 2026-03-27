@extends('layouts.list-page')

@section('page_title', __('patient_images.page_title'))

@section('table_id', 'patient_images_table')

@section('page_css')
    <style>
        #patient_images_table {
            min-width: 960px;
        }

        .portlet-body.patient-images-empty #patient_images_table_wrapper .table-scrollable,
        .portlet-body.patient-images-empty #patient_images_table_wrapper > .row:last-child {
            display: none;
        }

        .portlet-body.patient-images-empty #emptyState {
            margin-top: 16px;
            border-top: 1px solid #eef2f5;
            padding-top: 48px;
        }

        @media (max-width: 991px) {
            #patient_images_table_wrapper .table-scrollable {
                overflow-x: auto;
            }
        }

        @media (max-width: 767px) {
            .page-header-l1 {
                flex-direction: column;
            }

            .page-header-l1 .header-actions .btn {
                width: 100%;
            }
        }
    </style>
@endsection

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
@section('empty_desc', __('patient_images.no_images_found_filtered'))

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

    function syncPatientImagesLayoutState() {
        var $table = $(getTableSelector());
        var $portletBody = $table.closest('.portlet-body');
        $portletBody.toggleClass('patient-images-empty', $('#emptyState').is(':visible'));
    }

    $(document).on('init.dt draw.dt', getTableSelector(), function () {
        window.requestAnimationFrame(syncPatientImagesLayoutState);
    });
</script>
<script src="{{ asset('include_js/patient_images.js') }}"></script>
<script>
    $(function () {
        window.requestAnimationFrame(syncPatientImagesLayoutState);
    });
</script>
@endsection
