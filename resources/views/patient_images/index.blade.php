@extends('layouts.list-page')

@section('page_title', __('patient_images.page_title'))

@section('table_id', 'patient_images_table')

@section('page_css')
    <style>
        #patient_images_table_wrapper > .row:first-child,
        #patient_images_table_wrapper > .row:last-child {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 16px 0;
            margin: 0 0 20px;
        }

        #patient_images_table_wrapper > .row:first-child > [class*="col-"],
        #patient_images_table_wrapper > .row:last-child > [class*="col-"] {
            float: none;
        }

        #patient_images_table_wrapper .dataTables_length,
        #patient_images_table_wrapper .dataTables_filter {
            display: flex;
            align-items: center;
            margin: 0;
        }

        #patient_images_table_wrapper .dataTables_filter {
            justify-content: flex-end;
        }

        #patient_images_table_wrapper .dataTables_length label,
        #patient_images_table_wrapper .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
        }

        #patient_images_table_wrapper .dataTables_length select,
        #patient_images_table_wrapper .dataTables_filter input {
            height: 40px;
            border: 1px solid #d8e4ea;
            border-radius: 10px;
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        #patient_images_table_wrapper .dataTables_length select {
            min-width: 76px;
            padding: 0 28px 0 12px;
        }

        #patient_images_table_wrapper .dataTables_filter input {
            width: 220px;
            padding: 0 14px;
            background: #fff;
        }

        #patient_images_table_wrapper .dataTables_length select:focus,
        #patient_images_table_wrapper .dataTables_filter input:focus {
            border-color: #4eaeb6;
            box-shadow: 0 0 0 3px rgba(78, 174, 182, 0.12);
        }

        #patient_images_table_wrapper .table-scrollable {
            border: 1px solid #e3edf2;
            border-radius: 16px;
            overflow: auto !important;
            background: #fff;
        }

        #patient_images_table {
            margin-top: 0 !important;
            min-width: 960px;
        }

        #patient_images_table_wrapper .dataTables_info {
            font-size: 14px;
            color: #4b5563;
            padding-top: 0;
        }

        #patient_images_table_wrapper .dataTables_paginate {
            margin-top: 0;
            display: flex;
            justify-content: flex-end;
        }

        .portlet-body.patient-images-empty #patient_images_table_wrapper {
            display: none;
        }

        .portlet-body.patient-images-empty #emptyState {
            display: flex;
            min-height: 340px;
            margin-top: 16px;
            border: 1px dashed #d7e5eb;
            border-radius: 20px;
            background: linear-gradient(180deg, #fbfefe 0%, #f5fafb 100%);
        }

        .portlet-body.patient-images-empty #emptyState .empty-icon {
            width: 96px;
            height: 96px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: #eef7f8;
            color: #9fb7bf;
            margin-bottom: 20px;
        }

        .portlet-body.patient-images-empty #emptyState .empty-title {
            font-size: 28px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .portlet-body.patient-images-empty #emptyState .empty-desc {
            max-width: 420px;
            text-align: center;
            line-height: 1.7;
            color: #6b7280;
        }

        @media (max-width: 991px) {
            .page-header-l1 {
                align-items: stretch;
                gap: 16px;
            }

            .page-header-l1 .header-actions {
                justify-content: flex-start;
            }

            #patient_images_table_wrapper > .row:first-child,
            #patient_images_table_wrapper > .row:last-child {
                gap: 14px;
            }

            #patient_images_table_wrapper > .row:first-child > [class*="col-"],
            #patient_images_table_wrapper > .row:last-child > [class*="col-"] {
                width: 100%;
            }

            #patient_images_table_wrapper .dataTables_filter,
            #patient_images_table_wrapper .dataTables_paginate {
                justify-content: flex-start;
            }
        }

        @media (max-width: 767px) {
            .page-header-l1 {
                flex-direction: column;
            }

            .page-header-l1 .header-actions .btn {
                width: 100%;
            }

            #patient_images_table_wrapper .dataTables_length label,
            #patient_images_table_wrapper .dataTables_filter label {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            #patient_images_table_wrapper .dataTables_length select,
            #patient_images_table_wrapper .dataTables_filter input {
                width: 100%;
            }

            .portlet-body.patient-images-empty #emptyState {
                min-height: 280px;
                padding: 32px 20px;
            }

            .portlet-body.patient-images-empty #emptyState .empty-title {
                font-size: 22px;
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
