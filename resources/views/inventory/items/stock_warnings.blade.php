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
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'inventory': @json(__('inventory')),
                'common': @json(__('common'))
            });

            $('#warnings-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/inventory-stock-warnings') }}",
                    data: function (d) {}
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'item_code', name: 'item_code'},
                    {data: 'name', name: 'name'},
                    {data: 'category_name', name: 'category_name'},
                    {data: 'unit', name: 'unit'},
                    {data: 'current_stock', name: 'current_stock'},
                    {data: 'stock_warning_level', name: 'stock_warning_level'},
                    {data: 'shortage', name: 'shortage'}
                ]
            });
        });
    </script>
@endsection
