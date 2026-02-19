@extends('layouts.list-page')

@section('page_title', __('inventory.stock_in'))
@section('table_id', 'stock-ins-table')

@section('header_actions')
    <a class="btn btn-primary" href="{{ route('stock-ins.create') }}">
        {{ __('inventory.create_stock_in') }} <i class="fa fa-plus"></i>
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('inventory.status') }}</div>
            <select id="filter-status" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                <option value="draft">{{ __('inventory.status_draft') }}</option>
                <option value="confirmed">{{ __('inventory.status_confirmed') }}</option>
                <option value="cancelled">{{ __('inventory.status_cancelled') }}</option>
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
        <div class="col-md-3 text-right filter-actions">
            <button class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button class="btn btn-primary" onclick="filterTable()">{{ __('inventory.filter') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('inventory.sn') }}</th>
    <th>{{ __('inventory.stock_in_no') }}</th>
    <th>{{ __('inventory.stock_in_date') }}</th>
    <th>{{ __('inventory.supplier') }}</th>
    <th>{{ __('inventory.items_count') }}</th>
    <th>{{ __('inventory.total_amount') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('inventory.added_by') }}</th>
    <th>{{ __('common.view') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'inventory': @json(__('inventory')),
                'common': @json(__('common'))
            });

            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });
            loadTable();
        });

        function loadTable() {
            dataTable = $('#stock-ins-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/stock-ins') }}",
                    data: function (d) {
                        d.status = $('#filter-status').val();
                        d.start_date = $('#start-date').val();
                        d.end_date = $('#end-date').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'stock_in_no', name: 'stock_in_no'},
                    {data: 'stock_in_date', name: 'stock_in_date'},
                    {data: 'supplier_name', name: 'supplier_name'},
                    {data: 'items_count', name: 'items_count'},
                    {data: 'total_amount', name: 'total_amount'},
                    {data: 'status_label', name: 'status_label', orderable: false, searchable: false},
                    {data: 'added_by', name: 'added_by'},
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
                    url: "/stock-ins/" + id,
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
