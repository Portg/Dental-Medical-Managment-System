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
                    <span class="caption-subject">{{ __('inventory.stock_out') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="{{ route('stock-outs.create') }}">
                                    {{ __('inventory.create_stock_out') }} <i class="fa fa-plus"></i>
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
                            <select id="filter-type" class="form-control">
                                <option value="">{{ __('inventory.out_type') }}</option>
                                <option value="treatment">{{ __('inventory.out_type_treatment') }}</option>
                                <option value="department">{{ __('inventory.out_type_department') }}</option>
                                <option value="damage">{{ __('inventory.out_type_damage') }}</option>
                                <option value="other">{{ __('inventory.out_type_other') }}</option>
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
                       id="stock-outs-table">
                    <thead>
                    <tr>
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
            table = $('#stock-outs-table').DataTable({
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
                    url: "/stock-outs/" + id,
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
