@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link href="{{ asset('backend/assets/global/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('inventory.service_consumables') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{ __('inventory.service') }}</label>
                            <select id="filter-service" class="form-control">
                                <option value="">{{ __('inventory.select_service') }}</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button class="btn btn-default form-control" onclick="filterTable()">{{ __('inventory.filter') }}</button>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="well">
                    <h4>{{ __('inventory.add_item') }}</h4>
                    <form id="consumable-form" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label>{{ __('inventory.service') }} <span class="text-danger">*</span></label>
                                <select name="medical_service_id" id="service-select" class="form-control" required>
                                    <option value="">{{ __('inventory.select_service') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>{{ __('inventory.item') }} <span class="text-danger">*</span></label>
                                <select name="inventory_item_id" id="item-select" class="form-control select2-item" style="width: 100%" required>
                                    <option value="">{{ __('inventory.select_item') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>{{ __('inventory.consumable_qty') }} <span class="text-danger">*</span></label>
                                <input type="number" name="qty" class="form-control" step="0.01" min="0.01" value="1" required>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="is_required" value="1" checked> {{ __('inventory.required') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-10">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success" onclick="addConsumable()">
                                    <i class="fa fa-plus"></i> {{ __('common.add') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="consumables-table">
                    <thead>
                    <tr>
                        <th>{{ __('inventory.sn') }}</th>
                        <th>{{ __('inventory.service') }}</th>
                        <th>{{ __('inventory.item_code') }}</th>
                        <th>{{ __('inventory.item_name') }}</th>
                        <th>{{ __('inventory.unit') }}</th>
                        <th>{{ __('inventory.consumable_qty') }}</th>
                        <th>{{ __('inventory.required') }}</th>
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
    <script src="{{ asset('backend/assets/global/plugins/select2/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        var table;

        $(function () {
            LanguageManager.loadAllFromPHP({
                'inventory': @json(__('inventory')),
                'common': @json(__('common'))
            });

            // Initialize Select2 for item search
            $('.select2-item').select2({
                ajax: {
                    url: '/inventory-items-search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return { results: data };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: "{{ __('inventory.select_item') }}"
            });

            loadTable();
        });

        function loadTable() {
            table = $('#consumables-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/service-consumables') }}",
                    data: function (d) {
                        d.medical_service_id = $('#filter-service').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'service_name', name: 'service_name'},
                    {data: 'item_code', name: 'item_code'},
                    {data: 'item_name', name: 'item_name'},
                    {data: 'unit', name: 'unit'},
                    {data: 'qty', name: 'qty'},
                    {data: 'is_required_label', name: 'is_required_label', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });
        }

        function filterTable() {
            table.ajax.reload();
        }

        function addConsumable() {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'POST',
                data: $('#consumable-form').serialize(),
                url: "/service-consumables",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    if (data.status) {
                        swal("{{ __('common.alert') }}", data.message, "success");
                        table.ajax.reload();
                        $('#consumable-form')[0].reset();
                        $('#item-select').val(null).trigger('change');
                    } else {
                        swal("{{ __('common.alert') }}", data.message, "error");
                    }
                },
                error: function (request) {
                    $.LoadingOverlay("hide");
                    if (request.responseJSON && request.responseJSON.errors) {
                        var errors = request.responseJSON.errors;
                        var message = '';
                        $.each(errors, function (key, value) {
                            message += value + '\n';
                        });
                        swal("{{ __('common.error') }}", message, "error");
                    }
                }
            });
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
                    url: "/service-consumables/" + id,
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
