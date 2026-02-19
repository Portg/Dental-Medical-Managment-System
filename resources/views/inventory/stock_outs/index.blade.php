@extends('layouts.list-page')

@section('page_title', __('inventory.stock_out'))
@section('table_id', 'stock-outs-table')

@section('header_actions')
    <a class="btn btn-primary" href="{{ route('stock-outs.create') }}">
        {{ __('inventory.create_stock_out') }} <i class="fa fa-plus"></i>
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-2">
            <div class="filter-label">{{ __('inventory.status') }}</div>
            <select id="filter-status" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                <option value="draft">{{ __('inventory.status_draft') }}</option>
                <option value="confirmed">{{ __('inventory.status_confirmed') }}</option>
                <option value="cancelled">{{ __('inventory.status_cancelled') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="filter-label">{{ __('inventory.out_type') }}</div>
            <select id="filter-type" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                <option value="treatment">{{ __('inventory.out_type_treatment') }}</option>
                <option value="department">{{ __('inventory.out_type_department') }}</option>
                <option value="damage">{{ __('inventory.out_type_damage') }}</option>
                <option value="other">{{ __('inventory.out_type_other') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('common.start_date') }}</div>
            <input type="text" id="start-date" class="form-control datepicker" placeholder="{{ __('common.start_date') }}">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('common.end_date') }}</div>
            <input type="text" id="end-date" class="form-control datepicker" placeholder="{{ __('common.end_date') }}">
        </div>
        <div class="col-md-2 text-right filter-actions">
            <button class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button class="btn btn-primary" onclick="filterTable()">{{ __('inventory.filter') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('inventory.sn') }}</th>
    <th>{{ __('inventory.stock_out_no') }}</th>
    <th>{{ __('inventory.stock_out_date') }}</th>
    <th>{{ __('inventory.out_type') }}</th>
    <th>{{ __('patient.patient') }}</th>
    <th>{{ __('inventory.items_count') }}</th>
    <th>{{ __('inventory.total_amount') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('common.view') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'inventory': @json(__('inventory')),
                'common': @json(__('common')),
                'patient': @json(__('patient'))
            });

            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });
            loadTable();
        });

        function loadTable() {
            dataTable = $('#stock-outs-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/stock-outs') }}",
                    data: function (d) {
                        d.status = $('#filter-status').val();
                        d.out_type = $('#filter-type').val();
                        d.start_date = $('#start-date').val();
                        d.end_date = $('#end-date').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'stock_out_no', name: 'stock_out_no'},
                    {data: 'stock_out_date', name: 'stock_out_date'},
                    {data: 'out_type_label', name: 'out_type_label'},
                    {data: 'patient_name', name: 'patient_name'},
                    {data: 'items_count', name: 'items_count'},
                    {data: 'total_amount', name: 'total_amount'},
                    {data: 'status_label', name: 'status_label', orderable: false, searchable: false},
                    {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        }

        function filterTable() {
            dataTable.ajax.reload();
        }

        function deleteRecord(id) {
            swal({
                title: "{{ __('common.are_you_sure') }}",
                text: "{{ __('common.delete_confirm') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "{{ __('common.yes_delete_it') }}",
                closeOnConfirm: false
            }, function () {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'delete',
                    data: { _token: CSRF_TOKEN },
                    url: "/stock-outs/" + id,
                    success: function (data) {
                        $.LoadingOverlay("hide");
                        swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "danger");
                        if (data.status) {
                            dataTable.ajax.reload();
                        }
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        swal("{{ __('common.error') }}", "{{ __('messages.error_occurred_later') }}", "error");
                    }
                });
            });
        }
    </script>
@endsection
