@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    <link href="{{ asset('css/inventory-query.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
    <div class="page-bar">
        <h3 class="page-title">{{ __('inventory.inventory_query') }}</h3>
    </div>

    <div class="portlet light">
        <div class="portlet-body">

            {{-- Tab Navigation --}}
            <ul class="nav nav-tabs inv-query-tabs" id="inventoryQueryTabs" role="tablist">
                <li class="active">
                    <a href="#tab-stock-summary" data-toggle="tab" role="tab">
                        <i class="fa fa-list"></i> {{ __('inventory.stock_summary') }}
                    </a>
                </li>
                <li>
                    <a href="#tab-batch-detail" data-toggle="tab" role="tab">
                        <i class="fa fa-cubes"></i> {{ __('inventory.batch_detail') }}
                    </a>
                </li>
                <li>
                    <a href="#tab-movement-summary" data-toggle="tab" role="tab">
                        <i class="fa fa-bar-chart"></i> {{ __('inventory.movement_summary') }}
                    </a>
                </li>
                <li>
                    <a href="#tab-movement-detail" data-toggle="tab" role="tab">
                        <i class="fa fa-exchange"></i> {{ __('inventory.movement_detail') }}
                    </a>
                </li>
            </ul>

            <div class="tab-content inv-query-tab-content">

                {{-- ===== Tab 1: 库存汇总 ===== --}}
                <div class="tab-pane active" id="tab-stock-summary">
                    <div class="inv-query-filter-row">
                        <div class="form-inline">
                            <div class="form-group">
                                <label>{{ __('inventory.category') }}</label>
                                <select id="ss-category" class="form-control input-sm">
                                    <option value="">{{ __('common.all') }}</option>
                                    @foreach(\App\InventoryCategory::active()->ordered()->get() as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="stock-summary-table" class="table table-striped table-hover dt-table">
                            <thead>
                                <tr>
                                    <th>{{ __('inventory.sn') }}</th>
                                    <th>{{ __('inventory.item_code') }}</th>
                                    <th>{{ __('inventory.item_name') }}</th>
                                    <th>{{ __('inventory.specification') }}</th>
                                    <th>{{ __('inventory.unit') }}</th>
                                    <th>{{ __('inventory.current_vs_warning') }}</th>
                                    <th class="col-avg-cost">{{ __('inventory.average_cost') }}</th>
                                    <th>{{ __('inventory.category') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- ===== Tab 2: 批次明细 ===== --}}
                <div class="tab-pane" id="tab-batch-detail">
                    <div class="inv-query-filter-row">
                        <div class="form-inline">
                            <div class="form-group">
                                <label>{{ __('inventory.category') }}</label>
                                <select id="bd-category" class="form-control input-sm">
                                    <option value="">{{ __('common.all') }}</option>
                                    @foreach(\App\InventoryCategory::active()->ordered()->get() as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('inventory.expiry_status') }}</label>
                                <select id="bd-expiry-status" class="form-control input-sm">
                                    <option value="">{{ __('common.all') }}</option>
                                    <option value="normal">{{ __('inventory.expiry_normal') }}</option>
                                    <option value="near">{{ __('inventory.expiry_near') }}</option>
                                    <option value="expired">{{ __('inventory.expiry_expired') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="batch-detail-table" class="table table-striped table-hover dt-table">
                            <thead>
                                <tr>
                                    <th>{{ __('inventory.sn') }}</th>
                                    <th>{{ __('inventory.item_name') }}</th>
                                    <th>{{ __('inventory.category') }}</th>
                                    <th>{{ __('inventory.batch_no') }}</th>
                                    <th>{{ __('inventory.quantity') }}</th>
                                    <th>{{ __('inventory.expiry_date') }}</th>
                                    <th>{{ __('inventory.expiry_status') }}</th>
                                    <th>{{ __('inventory.created_at') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- ===== Tab 3: 出入库查询（聚合） ===== --}}
                <div class="tab-pane" id="tab-movement-summary">
                    <div class="inv-query-filter-row">
                        <div class="form-inline">
                            <div class="form-group">
                                <label>{{ __('common.start_date') }}</label>
                                <input type="text" id="ms-start-date" class="form-control input-sm datepicker"
                                       placeholder="{{ __('common.start_date') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('common.end_date') }}</label>
                                <input type="text" id="ms-end-date" class="form-control input-sm datepicker"
                                       placeholder="{{ __('common.end_date') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('inventory.item_name') }} / {{ __('inventory.item_code') }}</label>
                                <input type="text" id="ms-keyword" class="form-control input-sm"
                                       placeholder="{{ __('common.search') }}">
                            </div>
                            <button class="btn btn-primary btn-sm" id="ms-search-btn">
                                <i class="fa fa-search"></i> {{ __('inventory.filter') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="movement-summary-table" class="table table-striped table-hover dt-table">
                            <thead>
                                <tr>
                                    <th>{{ __('inventory.sn') }}</th>
                                    <th>{{ __('inventory.item_code') }}</th>
                                    <th>{{ __('inventory.item_name') }}</th>
                                    <th>{{ __('inventory.category') }}</th>
                                    <th>{{ __('inventory.total_in_qty') }}</th>
                                    <th>{{ __('inventory.total_out_qty') }}</th>
                                    <th>{{ __('inventory.net_change') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                {{-- ===== Tab 4: 出入库明细（流水） ===== --}}
                <div class="tab-pane" id="tab-movement-detail">
                    <div class="inv-query-filter-row">
                        <div class="form-inline">
                            <div class="form-group">
                                <label>{{ __('common.start_date') }}</label>
                                <input type="text" id="md-start-date" class="form-control input-sm datepicker"
                                       placeholder="{{ __('common.start_date') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('common.end_date') }}</label>
                                <input type="text" id="md-end-date" class="form-control input-sm datepicker"
                                       placeholder="{{ __('common.end_date') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('inventory.out_type') }}</label>
                                <select id="md-out-type" class="form-control input-sm">
                                    <option value="">{{ __('common.all') }}</option>
                                    @foreach(\App\DictItem::listByType('stock_out_type') as $item)
                                        <option value="{{ $item->code }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('inventory.status') }}</label>
                                <select id="md-status" class="form-control input-sm">
                                    <option value="">{{ __('common.all') }}</option>
                                    <option value="draft">{{ __('inventory.status_draft') }}</option>
                                    <option value="confirmed">{{ __('inventory.status_confirmed') }}</option>
                                    <option value="cancelled">{{ __('inventory.status_cancelled') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('inventory.item_name') }} / {{ __('inventory.item_code') }}</label>
                                <input type="text" id="md-keyword" class="form-control input-sm"
                                       placeholder="{{ __('common.search') }}">
                            </div>
                            <button class="btn btn-primary btn-sm" id="md-search-btn">
                                <i class="fa fa-search"></i> {{ __('inventory.filter') }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="movement-detail-table" class="table table-striped table-hover dt-table">
                            <thead>
                                <tr>
                                    <th>{{ __('inventory.sn') }}</th>
                                    <th>{{ __('inventory.movement_type') }}</th>
                                    <th>{{ __('inventory.record_no') }}</th>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('inventory.item_name') }}</th>
                                    <th>{{ __('inventory.quantity') }}</th>
                                    <th>{{ __('inventory.out_type') }}</th>
                                    <th>{{ __('inventory.status') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>{{-- /.tab-content --}}
        </div>{{-- /.portlet-body --}}
    </div>{{-- /.portlet --}}
@endsection

@section('js')
    <script>
        LanguageManager.loadAllFromPHP({
            'inventory': @json(__('inventory')),
            'common': @json(__('common'))
        });
    </script>
    <script src="{{ asset('include_js/inventory_query.js') }}?v={{ filemtime(public_path('include_js/inventory_query.js')) }}"></script>
@endsection
