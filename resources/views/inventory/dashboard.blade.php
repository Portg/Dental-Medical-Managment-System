@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    <link href="{{ asset('css/inventory-dashboard.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
    <div class="page-bar">
        <h3 class="page-title">{{ __('inventory.inventory_dashboard') }}</h3>
        <div class="page-toolbar">
            <a href="{{ url('inventory/stock-ins') }}" class="btn btn-sm btn-default">
                {{ __('inventory.stock_in') }}
            </a>
            <a href="{{ url('inventory/stock-outs') }}" class="btn btn-sm btn-default">
                {{ __('inventory.stock_out') }}
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="inv-kpi-row">
        <div class="inv-kpi-card stock-in">
            <div class="kpi-value">{{ $kpi['today_stock_in'] }}</div>
            <div class="kpi-label">{{ __('inventory.today_stock_in') }}</div>
        </div>
        <div class="inv-kpi-card amount">
            <div class="kpi-value money">{{ number_format($kpi['month_stock_in_amount'], 2) }}</div>
            <div class="kpi-label">{{ __('inventory.month_stock_in_amount') }}</div>
        </div>
        <div class="inv-kpi-card stock-out">
            <div class="kpi-value">{{ $kpi['today_stock_out'] }}</div>
            <div class="kpi-label">{{ __('inventory.today_stock_out') }}</div>
        </div>
        <div class="inv-kpi-card billing">
            <div class="kpi-value">{{ $kpi['month_billing_stock_out'] }}</div>
            <div class="kpi-label">{{ __('inventory.month_billing_stock_out') }}</div>
        </div>
        <div class="inv-kpi-card warning">
            <div class="kpi-value">{{ $kpi['low_stock_count'] }}</div>
            <div class="kpi-label">{{ __('inventory.low_stock_count') }}</div>
        </div>
        <div class="inv-kpi-card expiry">
            <div class="kpi-value">{{ $kpi['expiry_warning_count'] }}</div>
            <div class="kpi-label">{{ __('inventory.expiry_warning_count') }}</div>
        </div>
    </div>

    {{-- Lists Row --}}
    <div class="inv-lists-row">
        {{-- Low Stock List --}}
        <div class="inv-list-panel portlet light">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-exclamation-triangle font-red"></i>
                    <span class="caption-subject bold">{{ __('inventory.low_stock_list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                @if($lowStockItems->isEmpty())
                    <p class="text-muted text-center">{{ __('common.no_data') }}</p>
                @else
                    <table class="table table-condensed table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('inventory.item_name') }}</th>
                                <th>{{ __('inventory.category') }}</th>
                                <th>{{ __('inventory.current_vs_warning') }}</th>
                                <th>{{ __('inventory.shortage_qty') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                                <tr>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->category->category_name ?? '-' }}</td>
                                    <td>{{ $item->current_stock }} / {{ $item->stock_warning_level }}</td>
                                    <td>
                                        <span class="shortage-badge">
                                            {{ $item->stock_warning_level - $item->current_stock }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Near Expiry List --}}
        <div class="inv-list-panel portlet light">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-clock-o font-yellow-casablanca"></i>
                    <span class="caption-subject bold">{{ __('inventory.expiry_warning_list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                @if($expiryBatches->isEmpty())
                    <p class="text-muted text-center">{{ __('common.no_data') }}</p>
                @else
                    <table class="table table-condensed table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('inventory.item_name') }}</th>
                                <th>{{ __('inventory.expiry_date_label') }}</th>
                                <th>{{ __('inventory.remaining_qty') }}</th>
                                <th>{{ __('inventory.days_remaining') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiryBatches as $batch)
                                @php
                                    $days = now()->diffInDays($batch->expiry_date, false);
                                    $badgeClass = $days <= 7 ? 'critical' : 'warning';
                                @endphp
                                <tr>
                                    <td>{{ $batch->inventoryItem->item_name ?? '-' }}</td>
                                    <td>{{ $batch->expiry_date }}</td>
                                    <td>{{ $batch->qty }}</td>
                                    <td>
                                        <span class="expiry-badge {{ $badgeClass }}">
                                            {{ $days }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
