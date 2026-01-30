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
                    <span class="caption-subject">{{ __('inventory.stock_in') }} - {{ $stockIn->stock_in_no }}</span>
                </div>
                <div class="actions">
                    <a href="{{ route('stock-ins.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                    @if($stockIn->isDraft())
                    <a href="{{ route('stock-ins.edit', $stockIn->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> {{ __('common.edit') }}
                    </a>
                    @endif
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>{{ __('inventory.stock_in_no') }}</strong></td>
                                <td>{{ $stockIn->stock_in_no }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.stock_in_date') }}</strong></td>
                                <td>{{ $stockIn->stock_in_date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.supplier') }}</strong></td>
                                <td>{{ $stockIn->supplier ? $stockIn->supplier->name : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.status') }}</strong></td>
                                <td>
                                    @if($stockIn->status == 'draft')
                                        <span class="badge badge-secondary">{{ __('inventory.status_draft') }}</span>
                                    @elseif($stockIn->status == 'confirmed')
                                        <span class="badge badge-success">{{ __('inventory.status_confirmed') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('inventory.status_cancelled') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.added_by') }}</strong></td>
                                <td>{{ $stockIn->addedBy ? $stockIn->addedBy->othername : '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>{{ __('inventory.notes') }}</strong></td>
                                <td>{{ $stockIn->notes ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.total_amount') }}</strong></td>
                                <td><strong>{{ number_format($stockIn->total_amount, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>
                <h4>{{ __('inventory.items') }}</h4>
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>{{ __('inventory.sn') }}</th>
                        <th>{{ __('inventory.item_code') }}</th>
                        <th>{{ __('inventory.item_name') }}</th>
                        <th>{{ __('inventory.specification') }}</th>
                        <th>{{ __('inventory.unit') }}</th>
                        <th>{{ __('inventory.quantity') }}</th>
                        <th>{{ __('inventory.unit_price') }}</th>
                        <th>{{ __('inventory.amount') }}</th>
                        <th>{{ __('inventory.batch_no') }}</th>
                        <th>{{ __('inventory.expiry_date') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($stockIn->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->inventoryItem->item_code ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->name ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->specification ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->unit ?? '-' }}</td>
                        <td>{{ number_format($item->qty, 2) }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                        <td>{{ $item->batch_no ?? '-' }}</td>
                        <td>{{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '-' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="7" class="text-right"><strong>{{ __('inventory.total_amount') }}</strong></td>
                        <td colspan="3"><strong>{{ number_format($stockIn->total_amount, 2) }}</strong></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
