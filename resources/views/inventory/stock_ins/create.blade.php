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
                    <span class="caption-subject">
                        {{ isset($stockIn) ? __('common.edit') . ' - ' . $stockIn->stock_in_no : __('inventory.create_stock_in') }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ route('stock-ins.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="stock-in-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="stock_in_id" name="id" value="{{ $stockIn->id ?? '' }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_in_no') }}</label>
                                <input type="text" class="form-control" value="{{ $stockIn->stock_in_no ?? $stock_in_no }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_in_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="stock_in_date" id="stock_in_date" class="form-control datepicker"
                                       value="{{ isset($stockIn) ? $stockIn->stock_in_date->format('Y-m-d') : date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.supplier') }}</label>
                                <select name="supplier_id" class="form-control">
                                    <option value="">{{ __('inventory.select_supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ isset($stockIn) && $stockIn->supplier_id == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('common.branch') }}</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ isset($stockIn) && $stockIn->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $stockIn->notes ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>

                @if(!isset($stockIn))
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="btn-save-header" onclick="saveHeader()">
                            {{ __('common.save') }} {{ __('inventory.stock_in') }}
                        </button>
                    </div>
                </div>
                @endif

                @if(isset($stockIn))
                <hr>
                <h4>{{ __('inventory.items') }}</h4>
                <div class="row">
                    <div class="col-md-12">
                        <div class="well">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>{{ __('inventory.item') }}</label>
                                    <select id="item-select" class="form-control select2-item" style="width: 100%">
                                        <option value="">{{ __('inventory.select_item') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.quantity') }}</label>
                                    <input type="number" id="item-qty" class="form-control" step="0.01" min="0.01" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.unit_price') }}</label>
                                    <input type="number" id="item-price" class="form-control" step="0.01" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.batch_no') }}</label>
                                    <input type="text" id="item-batch" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.expiry_date') }}</label>
                                    <input type="text" id="item-expiry" class="form-control datepicker">
                                </div>
                            </div>
                            <div class="row mt-10">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-success" onclick="addItem()">
                                        <i class="fa fa-plus"></i> {{ __('inventory.add_item') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="table table-striped table-bordered" id="items-table">
                    <thead>
                    <tr>
                        <th>{{ __('inventory.sn') }}</th>
                        <th>{{ __('inventory.item_code') }}</th>
                        <th>{{ __('inventory.item_name') }}</th>
                        <th>{{ __('inventory.specification') }}</th>
                        <th>{{ __('inventory.quantity') }}</th>
                        <th>{{ __('inventory.unit_price') }}</th>
                        <th>{{ __('inventory.amount') }}</th>
                        <th>{{ __('inventory.batch_no') }}</th>
                        <th>{{ __('inventory.expiry_date') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="6" class="text-right"><strong>{{ __('inventory.total_amount') }}</strong></td>
                        <td colspan="4"><strong id="total-amount">{{ number_format($stockIn->total_amount, 2) }}</strong></td>
                    </tr>
                    </tfoot>
                </table>

                <div class="row mt-20">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" onclick="updateHeader()">
                            <i class="fa fa-save"></i> {{ __('common.save_changes') }}
                        </button>
                        @if($stockIn->isDraft())
                        <button type="button" class="btn btn-success" onclick="confirmStockIn()">
                            <i class="fa fa-check"></i> {{ __('inventory.confirm_stock_in') }}
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cancelStockIn()">
                            <i class="fa fa-times"></i> {{ __('inventory.cancel_record') }}
                        </button>
                        @endif
                    </div>
                </div>
                @endif
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
        LanguageManager.loadAllFromPHP({
            'inventory': @json(__('inventory')),
            'common': @json(__('common'))
        });
    </script>
    <script src="{{ asset('include_js/stock_ins.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        var stockInId = "{{ $stockIn->id ?? '' }}";
        var csrfToken = "{{ csrf_token() }}";

        $(function () {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
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

            // When item is selected, populate reference price
            $('#item-select').on('select2:select', function (e) {
                var data = e.params.data;
                $('#item-price').val(data.reference_price || 0);
            });

            @if(isset($stockIn))
            loadItems();
            @endif
        });

        function saveHeader() {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'POST',
                data: $('#stock-in-form').serialize(),
                url: "/stock-ins",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    if (data.status && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                    }
                },
                error: function (request) {
                    $.LoadingOverlay("hide");
                    handleErrors(request);
                }
            });
        }

        function updateHeader() {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'PUT',
                data: $('#stock-in-form').serialize(),
                url: "/stock-ins/" + stockInId,
                success: function (data) {
                    $.LoadingOverlay("hide");
                    swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "error");
                },
                error: function (request) {
                    $.LoadingOverlay("hide");
                    handleErrors(request);
                }
            });
        }

        function handleErrors(request) {
            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                $('.alert-danger ul').empty();
                $.each(errors, function (key, value) {
                    $('.alert-danger ul').append('<li>' + value + '</li>');
                });
                $('.alert-danger').show();
            }
        }
    </script>
@endsection
