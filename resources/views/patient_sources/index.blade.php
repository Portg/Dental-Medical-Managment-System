{{--
    Patient Sources List Page
    Extends the list-page base template
--}}
@extends('layouts.list-page')

{{-- ========================================================================
     Required Sections
     ======================================================================== --}}

@section('page_title')
    {{ __('patient_tags.patient_sources') }}
@endsection

@section('table_id', 'sources-table')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('patient_tags.name') }}</th>
    <th>{{ __('patient_tags.code') }}</th>
    <th>{{ __('patient_tags.patients_count') }}</th>
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
                   placeholder="{{ __('patient_tags.search_sources') }}">
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
@section('empty_icon', 'fa-sitemap')

@section('empty_title')
    {{ __('patient_tags.no_sources_found') }}
@endsection

@section('empty_desc')
    {{ __('patient_tags.click_add_source_to_start') }}
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
    @include('patient_sources.create')
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script type="text/javascript">
    // Load translations
    LanguageManager.loadAllFromPHP({
        'patient_tags': @json(__('patient_tags')),
        'common': @json(__('common'))
    });

    // ==========================================================================
    // DataTable Initialization
    // ==========================================================================

    $(function() {
        // Initialize DataTable
        dataTable = $('#sources-table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/patient-sources/') }}",
                data: function(d) {
                    d.quick_search = $('#quickSearch').val();
                    d.status = $('#filter_status').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'code', name: 'code'},
                {data: 'patients_count', name: 'patients_count'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Setup empty state handler
        setupEmptyStateHandler();

        // Quick search with debounce
        $('#quickSearch').on('keyup', debounce(function() {
            dataTable.draw(true);
        }, 300));

        // Status filter auto-apply
        $('#filter_status').on('change', function() {
            dataTable.draw(true);
        });
    });

    // ==========================================================================
    // Override Base Functions
    // ==========================================================================

    function doSearch() {
        if (dataTable) {
            dataTable.draw(true);
        }
    }

    function clearFilters() {
        $('#quickSearch').val('');
        $('#filter_status').val('');
        doSearch();
    }

    function createRecord() {
        $("#source-form")[0].reset();
        $('#source_id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#source-modal .modal-title').text('{{ __("patient_tags.create_source") }}');
        $('#source-modal').modal('show');
    }

    // Alias for controller compatibility
    function createSource() {
        createRecord();
    }

    function editRecord(id) {
        $.LoadingOverlay("show");
        $("#source-form")[0].reset();
        $('#source_id').val('');
        $('#btn-save').attr('disabled', false);

        $.ajax({
            type: 'get',
            url: "{{ url('/patient-sources') }}/" + id,
            success: function(response) {
                if (response.status) {
                    var data = response.data;
                    $('#source_id').val(data.id);
                    $('[name="name"]').val(data.name);
                    $('[name="code"]').val(data.code);
                    $('[name="description"]').val(data.description);
                    $('[name="is_active"]').prop('checked', data.is_active);
                }
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#source-modal .modal-title').text('{{ __("patient_tags.edit_source") }}');
                $('#source-modal').modal('show');
            },
            error: function() {
                $.LoadingOverlay("hide");
            }
        });
    }

    // Alias for controller compatibility
    function editSource(id) {
        editRecord(id);
    }

    function deleteRecord(id) {
        var sweetAlertLang = LanguageManager.getSweetAlertLang();
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('patient_tags.delete_source_confirm') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: sweetAlertLang.cancel,
            closeOnConfirm: false
        }, function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "{{ url('/patient-sources') }}/" + id,
                success: function(data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function() {
                    $.LoadingOverlay("hide");
                }
            });
        });
    }

    // Alias for controller compatibility
    function deleteSource(id) {
        deleteRecord(id);
    }

    // ==========================================================================
    // Form CRUD Functions
    // ==========================================================================

    function save_source() {
        var id = $('#source_id').val();
        if (id === "") {
            save_new_source();
        } else {
            update_source();
        }
    }

    function save_new_source() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'POST',
            data: $('#source-form').serialize(),
            url: "{{ url('/patient-sources') }}",
            success: function(data) {
                $('#source-modal').modal('hide');
                $.LoadingOverlay("hide");
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text('{{ __("common.save_record") }}');
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    function update_source() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.updating") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#source-form').serialize(),
            url: "{{ url('/patient-sources') }}/" + $('#source_id').val(),
            success: function(data) {
                $('#source-modal').modal('hide');
                $.LoadingOverlay("hide");
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text('{{ __("common.update_record") }}');
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }
</script>
@endsection
