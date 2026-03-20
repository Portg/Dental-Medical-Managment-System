@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/inventory-check.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">
                        {{ __('inventory.inventory_checks') }} - {{ $check->check_no }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('inventory-checks') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                    @if($check->isDraft())
                        <button type="button" class="btn btn-primary" onclick="saveActualQty({{ $check->id }})">
                            <i class="fa fa-save"></i> {{ __('common.save') }}
                        </button>
                        <button type="button" class="btn btn-success" onclick="confirmCheck({{ $check->id }})">
                            <i class="fa fa-check"></i> {{ __('inventory.confirm_check') }}
                        </button>
                    @endif
                </div>
            </div>

            <div class="portlet-body">
                {{-- 基本信息 --}}
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered check-info-table">
                            <tr>
                                <td><strong>{{ __('inventory.check_no') }}</strong></td>
                                <td>{{ $check->check_no }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.category') }}</strong></td>
                                <td>{{ $check->category ? $check->category->name : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.check_date') }}</strong></td>
                                <td>{{ $check->check_date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.status') }}</strong></td>
                                <td>
                                    @php
                                        $badgeClass = $check->isDraft() ? 'badge-secondary' : 'badge-success';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $check->status_label }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered check-info-table">
                            <tr>
                                <td><strong>{{ __('inventory.added_by') }}</strong></td>
                                <td>{{ $check->addedBy ? $check->addedBy->othername : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.created_at') }}</strong></td>
                                <td>{{ $check->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @if($check->isConfirmed())
                            <tr>
                                <td><strong>{{ __('inventory.checked_by') }}</strong></td>
                                <td>{{ $check->checkedBy ? $check->checkedBy->othername : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.confirmed_at') }}</strong></td>
                                <td>{{ $check->confirmed_at ? $check->confirmed_at->format('Y-m-d H:i') : '-' }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td><strong>{{ __('inventory.notes') }}</strong></td>
                                <td>{{ $check->notes ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                {{-- 物品明细 --}}
                <h4>{{ __('inventory.items') }}</h4>
                <table class="table table-striped table-bordered check-items-table" id="check-items-table">
                    <thead>
                        <tr>
                            <th>{{ __('inventory.sn') }}</th>
                            <th>{{ __('inventory.item_code') }}</th>
                            <th>{{ __('inventory.item_name') }}</th>
                            <th>{{ __('inventory.specification') }}</th>
                            <th>{{ __('inventory.unit') }}</th>
                            <th>{{ __('inventory.system_qty') }}</th>
                            <th>{{ __('inventory.actual_qty') }}</th>
                            <th>{{ __('inventory.diff_qty') }}</th>
                            <th>{{ __('inventory.deviation_rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($check->items as $index => $item)
                        <tr data-check-item-id="{{ $item->id }}"
                            data-system-qty="{{ $item->system_qty }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->inventoryItem->item_code ?? '-' }}</td>
                            <td>{{ $item->inventoryItem->name ?? '-' }}</td>
                            <td>{{ $item->inventoryItem->specification ?? '-' }}</td>
                            <td>{{ $item->inventoryItem->unit ?? '-' }}</td>
                            <td>{{ $item->system_qty }}</td>
                            <td>
                                @if($check->isDraft())
                                    <input type="number"
                                           class="form-control form-control-sm actual-qty-input"
                                           value="{{ $item->actual_qty ?? '' }}"
                                           min="0"
                                           step="0.01"
                                           style="width:100px">
                                @else
                                    {{ $item->actual_qty ?? '-' }}
                                @endif
                            </td>
                            <td class="diff-cell">
                                @if(!is_null($item->diff_qty))
                                    <span class="{{ $item->diff_qty < 0 ? 'text-loss' : ($item->diff_qty > 0 ? 'text-surplus' : 'text-no-diff') }}">
                                        {{ $item->diff_qty > 0 ? '+' : '' }}{{ $item->diff_qty }}
                                    </span>
                                @else
                                    <span class="text-no-diff">-</span>
                                @endif
                            </td>
                            <td class="deviation-cell">
                                @if(!is_null($item->actual_qty))
                                    <span>{{ round($item->deviation_rate * 100, 2) }}%</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($check->isDraft())
                <div class="row mt-20">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" onclick="saveActualQty({{ $check->id }})">
                            <i class="fa fa-save"></i> {{ __('common.save') }}
                        </button>
                        <button type="button" class="btn btn-success" onclick="confirmCheck({{ $check->id }})">
                            <i class="fa fa-check"></i> {{ __('inventory.confirm_check') }}
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.InventoryCheckConfig = {
        csrfToken: '{{ csrf_token() }}',
        checkId:   {{ $check->id }},
        isDraft:   {{ $check->isDraft() ? 'true' : 'false' }}
    };
    </script>
    <script src="{{ asset('include_js/inventory_check.js') }}?v={{ filemtime(public_path('include_js/inventory_check.js')) }}" type="text/javascript"></script>
    <script>
    $(function () {
        var cfg = window.InventoryCheckConfig || {};
        if (cfg.isDraft) { initCheckShow(); }
    });
    </script>
@endsection
