@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('inventory.stock_in') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="{{ route('stock-ins.create') }}">
                                    {{ __('inventory.create_stock_in') }} <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select id="filter-status" class="form-control">
                                <option value="">{{ __('inventory.status') }}</option>
                                <option value="draft">{{ __('inventory.status_draft') }}</option>
                                <option value="confirmed">{{ __('inventory.status_confirmed') }}</option>
                                <option value="cancelled">{{ __('inventory.status_cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="start-date" class="form-control datepicker" placeholder="{{ __('common.start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="end-date" class="form-control datepicker" placeholder="{{ __('common.end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-default" onclick="filterTable()">{{ __('inventory.filter') }}</button>
                        </div>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="stock-ins-table">
                    <thead>
                    <tr>
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
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        var table;

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
            table = $('#stock-ins-table').DataTable({
                destroy: true,
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
        }

        function filterTable() {
            table.ajax.reload();
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
                            table.ajax.reload();
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
