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
                    <i class="fa fa-clock-o text-danger"></i>
                    <span class="caption-subject">{{ __('inventory.expiry_warning') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-3">
                            <label>{{ __('inventory.warning_days') }}</label>
                            <select id="warning-days" class="form-control">
                                <option value="7">7 {{ __('datetime.days_unit') }}</option>
                                <option value="14">14 {{ __('datetime.days_unit') }}</option>
                                <option value="30" selected>30 {{ __('datetime.days_unit') }}</option>
                                <option value="60">60 {{ __('datetime.days_unit') }}</option>
                                <option value="90">90 {{ __('datetime.days_unit') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button class="btn btn-default form-control" onclick="filterTable()">{{ __('inventory.filter') }}</button>
                        </div>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="expiry-table">
                    <thead>
                    <tr>
                        <th>{{ __('inventory.sn') }}</th>
                        <th>{{ __('inventory.item_code') }}</th>
                        <th>{{ __('inventory.item_name') }}</th>
                        <th>{{ __('inventory.category') }}</th>
                        <th>{{ __('inventory.batch_no') }}</th>
                        <th>{{ __('inventory.expiry_date') }}</th>
                        <th>{{ __('inventory.days_to_expiry') }}</th>
                        <th>{{ __('inventory.quantity') }}</th>
                        <th>{{ __('inventory.status') }}</th>
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
    <script>
    window.ExpiryWarningsConfig = {
        ajaxUrl: '{{ url('/inventory-expiry-warnings') }}',
        i18n: {
            'inventory': @json(__('inventory')),
            'common':    @json(__('common')),
            'datetime':  @json(__('datetime'))
        }
    };
    </script>
    <script src="{{ asset('include_js/expiry_warnings.js') }}?v={{ filemtime(public_path('include_js/expiry_warnings.js')) }}" type="text/javascript"></script>
@endsection
