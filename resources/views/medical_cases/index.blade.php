{{--
    Medical Cases List Page
    =======================
    Extends the unified list-page template
--}}
@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('medical_cases.page_title'))
@section('table_id', 'medical_cases_table')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('medical-cases/create') }}" class="btn btn-primary">
        {{ __('common.add_new') }}
    </a>
@endsection

{{-- ========================================================================
     Filter Area
     ======================================================================== --}}
@section('filter_primary')
    <div class="col-md-3">
        <div class="filter-label">{{ __('common.search') }}</div>
        <input type="text" id="quickSearch" class="form-control"
               placeholder="{{ __('medical_cases.search_placeholder') }}">
    </div>
    <div class="col-md-2">
        <div class="filter-label">{{ __('medical_cases.status') }}</div>
        <select id="filter_status" class="form-control">
            <option value="">{{ __('common.all') }}</option>
            <option value="Open">{{ __('medical_cases.status_open') }}</option>
            <option value="In Progress">{{ __('medical_cases.status_in_progress') }}</option>
            <option value="Closed">{{ __('medical_cases.status_closed') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="filter-label">{{ __('medical_cases.doctor') }}</div>
        <select id="filter_doctor" class="form-control select2" style="width: 100%;"></select>
    </div>
@endsection

@section('filter_advanced')
    <div class="col-md-3">
        <div class="filter-label">{{ __('medical_cases.patient') }}</div>
        <select id="filter_patient" class="form-control select2" style="width: 100%;"></select>
    </div>
    <div class="col-md-5">
        <div class="filter-label">{{ __('datetime.date_range.title') }}</div>
        <div class="date-range-row">
            <div class="date-input">
                <input type="text" class="form-control start_date" id="filter_start_date"
                       placeholder="{{ __('datetime.date_range.start_date') }}">
            </div>
            <span class="date-separator">{{ __('common.until') }}</span>
            <div class="date-input">
                <input type="text" class="form-control end_date" id="filter_end_date"
                       placeholder="{{ __('datetime.date_range.end_date') }}">
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th style="width: 50px;">#</th>
    <th>{{ __('medical_cases.case_no') }}</th>
    <th>{{ __('medical_cases.title') }}</th>
    <th>{{ __('medical_cases.patient') }}</th>
    <th>{{ __('medical_cases.doctor') }}</th>
    <th>{{ __('medical_cases.case_date') }}</th>
    <th>{{ __('medical_cases.status') }}</th>
    <th style="width: 100px;">{{ __('common.actions') }}</th>
@endsection

{{-- ========================================================================
     Empty State
     ======================================================================== --}}
@section('empty_icon', 'fa-file-medical')

@section('empty_title')
    {{ __('medical_cases.no_cases_found') }}
@endsection

@section('empty_desc')
    {{ __('medical_cases.click_add_case_to_start') }}
@endsection

@section('empty_action')
    <a href="{{ url('medical-cases/create') }}" class="btn btn-primary">
        {{ __('common.add_new') }}
    </a>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'medical_cases': @json(__('medical_cases')),
        'messages': @json(__('messages'))
    });

    $(document).ready(function() {
        // Select2 AJAX 筛选器
        $('#filter_doctor').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('medical_cases.select_doctor') }}",
            allowClear: true,
            ajax: {
                url: '/search-doctor', dataType: 'json', delay: 250,
                data: function(p) { return { q: p.term || '' }; },
                processResults: function(d) { return { results: d }; },
                cache: true
            }
        });
        $('#filter_patient').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('medical_cases.select_patient') }}",
            allowClear: true, minimumInputLength: 2,
            ajax: {
                url: '/search-patient', dataType: 'json', delay: 250,
                data: function(p) { return { q: p.term }; },
                processResults: function(d) { return { results: d }; },
                cache: true
            }
        });

        // DataTable + 导航式 CRUD
        var dtm = new DataTableManager({
            tableId: '#medical_cases_table',
            ajaxUrl: '/medical-cases',
            order: [[5, 'desc']],
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'case_no', name: 'case_no'},
                {data: 'title', name: 'title'},
                {data: 'patient_name', name: 'patient_name'},
                {data: 'doctor_name', name: 'doctor_name'},
                {data: 'case_date', name: 'case_date'},
                {data: 'statusBadge', name: 'statusBadge', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            filterParams: function(d) {
                d.search_term = $('#quickSearch').val();
                d.status = $('#filter_status').val();
                d.doctor_id = $('#filter_doctor').val();
                d.patient_id = $('#filter_patient').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            },
            navigateCreate: true,
            createUrl: "{{ url('medical-cases/create') }}",
            navigateEdit: true,
            editUrl: '/medical-cases/{id}/edit'
        });

        dtm.initQuickSearch('#quickSearch');
        $('#filter_status, #filter_doctor, #filter_patient').on('change', function() { doSearch(); });
    });

    function viewRecord(id) {
        window.location.href = '/medical-cases/' + id;
    }

    function clearCustomFilters() {
        $('#quickSearch').val('');
        $('#filter_status').val('');
        $('#filter_doctor').val(null).trigger('change');
        $('#filter_patient').val(null).trigger('change');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
    }
</script>
@endsection
