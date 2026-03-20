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
    <script>
    LanguageManager.loadFromPHP(@json(__('inventory')), 'inventory');
    </script>
    <script src="{{ asset('include_js/service_consumables.js') }}?v={{ filemtime(public_path('include_js/service_consumables.js')) }}"></script>
@endsection
