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
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    <span class="caption-subject">{{ __('inventory.low_stock_warning') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="warnings-table">
                    <thead>
                    <tr>
                        <th>{{ __('inventory.sn') }}</th>
                        <th>{{ __('inventory.item_code') }}</th>
                        <th>{{ __('inventory.item_name') }}</th>
                        <th>{{ __('inventory.category') }}</th>
                        <th>{{ __('inventory.unit') }}</th>
                        <th>{{ __('inventory.current_stock') }}</th>
                        <th>{{ __('inventory.stock_warning_level') }}</th>
                        <th>{{ __('inventory.shortage') }}</th>
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
    window.StockWarningsConfig = {
        ajaxUrl: '{{ url('/inventory-stock-warnings') }}',
        i18n: {
            'inventory': @json(__('inventory')),
            'common':    @json(__('common'))
        }
    };
    </script>
    <script src="{{ asset('include_js/stock_warnings.js') }}?v={{ filemtime(public_path('include_js/stock_warnings.js')) }}" type="text/javascript"></script>
@endsection
