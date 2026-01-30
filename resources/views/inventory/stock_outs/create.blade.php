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
                        {{ isset($stockOut) ? __('common.edit') . ' - ' . $stockOut->stock_out_no : __('inventory.create_stock_out') }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ route('stock-outs.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="stock-out-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="stock_out_id" name="id" value="{{ $stockOut->id ?? '' }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_out_no') }}</label>
                                <input type="text" class="form-control" value="{{ $stockOut->stock_out_no ?? $stock_out_no }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_out_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="stock_out_date" id="stock_out_date" class="form-control datepicker"
                                       value="{{ isset($stockOut) ? $stockOut->stock_out_date->format('Y-m-d') : date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.out_type') }} <span class="text-danger">*</span></label>
                                <select name="out_type" id="out_type" class="form-control" required>
                                    <option value="">{{ __('inventory.select_type') }}</option>
                                    <option value="treatment" {{ isset($stockOut) && $stockOut->out_type == 'treatment' ? 'selected' : '' }}>{{ __('inventory.out_type_treatment') }}</option>
                                    <option value="department" {{ isset($stockOut) && $stockOut->out_type == 'department' ? 'selected' : '' }}>{{ __('inventory.out_type_department') }}</option>
                                    <option value="damage" {{ isset($stockOut) && $stockOut->out_type == 'damage' ? 'selected' : '' }}>{{ __('inventory.out_type_damage') }}</option>
                                    <option value="other" {{ isset($stockOut) && $stockOut->out_type == 'other' ? 'selected' : '' }}>{{ __('inventory.out_type_other') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('common.branch') }}</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ isset($stockOut) && $stockOut->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="treatment-fields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('patient.patient') }}</label>
                                <select name="patient_id" id="patient-select" class="form-control select2-patient" style="width: 100%">
                                    <option value="">{{ __('patient.select_patient') }}</option>
                                    @if(isset($stockOut) && $stockOut->patient)
                                        <option value="{{ $stockOut->patient_id }}" selected>{{ $stockOut->patient->fullname }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="department-fields" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.department') }}</label>
                                <input type="text" name="department" class="form-control" value="{{ $stockOut->department ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $stockOut->notes ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>

                @if(!isset($stockOut))
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="btn-save-header" onclick="saveHeader()">
                            {{ __('common.save') }} {{ __('inventory.stock_out') }}
                        </button>
                    </div>
                </div>
                @endif

                @if(isset($stockOut))
                <hr>
                <h4>{{ __('inventory.items') }}</h4>
                <div class="row">
                    <div class="col-md-12">
                        <div class="well">
                            <div class="row">
                                <div class="col-md-5">
                                    <label>{{ __('inventory.item') }}</label>
                                    <select id="item-select" class="form-control select2-item" style="width: 100%">
                                        <option value="">{{ __('inventory.select_item') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.current_stock') }}</label>
                                    <input type="text" id="item-stock" class="form-control" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.quantity') }}</label>
                                    <input type="number" id="item-qty" class="form-control" step="0.01" min="0.01" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-success form-control" onclick="addItem()">
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
                        <th>{{ __('inventory.current_stock') }}</th>
                        <th>{{ __('inventory.quantity') }}</th>
                        <th>{{ __('inventory.unit_cost') }}</th>
                        <th>{{ __('inventory.amount') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="7" class="text-right"><strong>{{ __('inventory.total_amount') }}</strong></td>
                        <td colspan="2"><strong id="total-amount">{{ number_format($stockOut->total_amount, 2) }}</strong></td>
                    </tr>
                    </tfoot>
                </table>

                <div class="row mt-20">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" onclick="updateHeader()">
                            <i class="fa fa-save"></i> {{ __('common.save_changes') }}
                        </button>
                        @if($stockOut->isDraft())
                        <button type="button" class="btn btn-success" onclick="confirmStockOut()">
                            <i class="fa fa-check"></i> {{ __('inventory.confirm_stock_out') }}
                        </button>
                        <button type="button" class="btn btn-danger" onclick="cancelStockOut()">
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
            'common': @json(__('common')),
            'patient': @json(__('patient'))
        });
    </script>
    <script src="{{ asset('include_js/stock_outs.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        var stockOutId = "{{ $stockOut->id ?? '' }}";
        var csrfToken = "{{ csrf_token() }}";

        $(function () {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });

            // Toggle fields based on out_type
            $('#out_type').change(function () {
                var type = $(this).val();
                $('#treatment-fields').hide();
                $('#department-fields').hide();
                if (type === 'treatment') {
                    $('#treatment-fields').show();
                } else if (type === 'department') {
                    $('#department-fields').show();
                }
            }).trigger('change');

            // Initialize Select2 for patient search
            $('.select2-patient').select2({
                ajax: {
                    url: '/search-patient',
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
                placeholder: "{{ __('patient.select_patient') }}"
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

            // When item is selected, show current stock
            $('#item-select').on('select2:select', function (e) {
                var data = e.params.data;
                $('#item-stock').val(data.current_stock || 0);
            });

            @if(isset($stockOut))
            loadItems();
            @endif
        });

        function saveHeader() {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'POST',
                data: $('#stock-out-form').serialize(),
                url: "/stock-outs",
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
                data: $('#stock-out-form').serialize(),
                url: "/stock-outs/" + stockOutId,
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
