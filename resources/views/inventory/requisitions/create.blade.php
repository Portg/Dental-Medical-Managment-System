@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link href="{{ asset('backend/assets/global/plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('css/requisition.css') }}">
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">
                        {{ isset($stockOut) ? __('common.edit') . ' - ' . $stockOut->stock_out_no : __('inventory.create_requisition') }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('requisitions') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="alert alert-danger" id="error-alert" style="display:none">
                    <ul id="error-list"></ul>
                </div>

                <form id="requisition-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="requisition_id" value="{{ $stockOut->id ?? '' }}">

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_out_no') }}</label>
                                <input type="text" class="form-control"
                                       value="{{ $stockOut->stock_out_no ?? $stockOutNo }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.requisition_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="stock_out_date" id="stock_out_date" class="form-control datepicker"
                                       value="{{ isset($stockOut) ? $stockOut->stock_out_date->format('Y-m-d') : date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.recipient') }}</label>
                                <input type="text" name="recipient" id="recipient" class="form-control"
                                       value="{{ $stockOut->recipient ?? $user->othername ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.department') }}</label>
                                <input type="text" name="department" id="department" class="form-control"
                                       value="{{ $stockOut->department ?? '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.notes') }}</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2">{{ $stockOut->notes ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>

                <hr>
                <h4>{{ __('inventory.items') }} <span class="text-danger">*</span></h4>

                <div class="row">
                    <div class="col-md-12">
                        <div class="well">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{ __('inventory.item') }}</label>
                                    <select id="add-item-select" class="form-control select2-item" style="width: 100%">
                                        <option value="">{{ __('inventory.select_item') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.current_stock') }}</label>
                                    <input type="text" id="add-item-stock" class="form-control" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ __('inventory.quantity') }} <span class="text-danger">*</span></label>
                                    <input type="number" id="add-item-qty" class="form-control" step="1" min="1" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-success form-control" onclick="addItemRow()">
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
                            <th>{{ __('inventory.item_name') }}</th>
                            <th>{{ __('inventory.specification') }}</th>
                            <th>{{ __('inventory.unit') }}</th>
                            <th>{{ __('inventory.quantity') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        @if(isset($stockOut))
                            @foreach($stockOut->items as $index => $item)
                                <tr data-item-id="{{ $item->inventory_item_id }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $item->inventoryItem->name ?? '-' }}
                                        <input type="hidden" name="items[{{ $index }}][inventory_item_id]" value="{{ $item->inventory_item_id }}">
                                    </td>
                                    <td>{{ $item->inventoryItem->specification ?? '-' }}</td>
                                    <td>{{ $item->inventoryItem->unit ?? '-' }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][qty]"
                                               class="form-control item-qty" step="1" min="1"
                                               value="{{ (int) $item->qty }}" style="width:80px">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-xs" onclick="removeRow(this)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>

                <div class="row mt-20">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-default" onclick="saveDraft()">
                            <i class="fa fa-save"></i> {{ __('inventory.save_draft') }}
                        </button>
                        <button type="button" class="btn btn-primary" onclick="saveAndSubmit()">
                            <i class="fa fa-paper-plane"></i> {{ __('inventory.submit_for_approval') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/select2/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.RequisitionFormConfig = {
        csrfToken:    '{{ csrf_token() }}',
        requisitionId: '{{ $stockOut->id ?? '' }}'
    };
    </script>
    <script src="{{ asset('include_js/requisition_form.js') }}?v={{ filemtime(public_path('include_js/requisition_form.js')) }}" type="text/javascript"></script>
    <script>
    $(function () { initRequisitionForm(); });
    </script>
@endsection
