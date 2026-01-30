{{--
    ============================================================================
    List Page Template Usage Example
    ============================================================================

    This is an example showing how to use the list-page base template.
    Copy this file and modify for your specific list page.

    File: resources/views/your-module/index.blade.php
    ============================================================================
--}}

@extends('layouts.list-page')

{{-- ========================================================================
     Required Sections
     ======================================================================== --}}

{{-- Page Title --}}
@section('page_title')
    {{ __('example.title') }}
@endsection

{{-- Table ID (must be unique) --}}
@section('table_id', 'example-table')

{{-- Table Headers --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.name') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.created_at') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection


{{-- ========================================================================
     Optional Sections
     ======================================================================== --}}

{{-- Header Actions (override default) --}}
@section('header_actions')
    <button type="button" class="btn btn-default" onclick="exportData()">
        {{ __('common.export') }}
    </button>
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('example.add_new') }}
    </button>
@endsection

{{-- Primary Filters (first row) --}}
@section('filter_primary')
    @include('components.filters.search-input', [
        'id' => 'quickSearch',
        'label' => __('common.search'),
        'placeholder' => __('example.search_placeholder'),
        'colClass' => 'col-md-3'
    ])

    @include('components.filters.select2', [
        'id' => 'filter_status',
        'label' => __('common.status'),
        'placeholder' => __('common.select_status'),
        'colClass' => 'col-md-2',
        'options' => [
            ['id' => 'active', 'text' => __('common.active')],
            ['id' => 'inactive', 'text' => __('common.inactive')],
        ]
    ])

    @include('components.filters.time-period', [
        'id' => 'period_selector',
        'label' => __('datetime.time_period'),
        'colClass' => 'col-md-2'
    ])
@endsection

{{-- Advanced Filters (collapsible second row) --}}
@section('filter_advanced')
    @include('components.filters.select2', [
        'id' => 'filter_category',
        'label' => __('example.category'),
        'placeholder' => __('example.select_category'),
        'colClass' => 'col-md-3',
        'ajaxUrl' => '/search-categories',
        'minInputLength' => 2
    ])

    @include('components.filters.date-range', [
        'label' => __('datetime.date_range.title'),
        'colClass' => 'col-md-6'
    ])
@endsection

{{-- Empty State --}}
@section('empty_icon', 'fa-folder-open')

@section('empty_title')
    {{ __('example.no_data') }}
@endsection

@section('empty_desc')
    {{ __('example.click_add_to_start') }}
@endsection

@section('empty_action')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        <i class="fa fa-plus"></i> {{ __('example.add_new') }}
    </button>
@endsection

{{-- Modal Dialogs --}}
@section('modals')
    @include('your-module.create')
@endsection

{{-- Page-specific CSS --}}
@section('page_css')
<style>
    /* Add any page-specific styles here */
</style>
@endsection

{{-- Page-specific JavaScript --}}
@section('page_js')
<script type="text/javascript">
    $(function() {
        // Initialize DataTable
        dataTable = $('#example-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/examples/') }}",
                data: function(d) {
                    d.quick_search = $('#quickSearch').val();
                    d.status = $('#filter_status').val();
                    d.category = $('#filter_category').val();
                    d.start_date = $('.start_date').val();
                    d.end_date = $('.end_date').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Setup empty state handler
        setupEmptyStateHandler();

        // Initialize Select2 with AJAX
        $('#filter_category').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('example.select_category') }}",
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '/search-categories',
                dataType: 'json',
                data: function(params) {
                    return { q: $.trim(params.term) };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            }
        });

        // Quick search with debounce
        $('#quickSearch').on('keyup', debounce(function() {
            dataTable.draw(true);
        }, 300));

        // Auto-filter on select change
        $('#filter_status, #period_selector').on('change', function() {
            dataTable.draw(true);
        });
    });

    // Override CRUD functions
    function createRecord() {
        $('#example-modal').modal('show');
    }

    function editRecord(id) {
        // Load and show edit modal
        $.get('/examples/' + id + '/edit', function(data) {
            // Populate form
            $('#example-modal').modal('show');
        });
    }

    function deleteRecord(id) {
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('example.delete_warning') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: LanguageManager.getSweetAlertLang().cancel,
            closeOnConfirm: false
        }, function() {
            $.ajax({
                type: 'DELETE',
                url: '/examples/' + id,
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(data) {
                    alert_dialog(data.message, data.status ? 'success' : 'danger');
                }
            });
        });
    }

    function exportData() {
        let params = {
            quick_search: $('#quickSearch').val(),
            status: $('#filter_status').val(),
            start_date: $('.start_date').val(),
            end_date: $('.end_date').val()
        };
        window.location.href = '/export-examples?' + $.param(params);
    }

    // Custom clear filters (optional)
    function clearCustomFilters() {
        // Reset period selector
        $('#period_selector').val('');
    }
</script>
@endsection
