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
                    <span class="caption-subject">{{ __('inventory.stock_out') }} - {{ $stockOut->stock_out_no }}</span>
                </div>
                <div class="actions">
                    <a href="{{ route('stock-outs.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                    @if($stockOut->isDraft())
                    <a href="{{ route('stock-outs.edit', $stockOut->id) }}" class="btn btn-primary">
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
                                <td><strong>{{ __('inventory.stock_out_no') }}</strong></td>
                                <td>{{ $stockOut->stock_out_no }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.stock_out_date') }}</strong></td>
                                <td>{{ $stockOut->stock_out_date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.out_type') }}</strong></td>
                                <td>{{ $stockOut->out_type_label }}</td>
                            </tr>
                            @if($stockOut->out_type == 'treatment' && $stockOut->patient)
                            <tr>
                                <td><strong>{{ __('patient.patient') }}</strong></td>
                                <td>{{ $stockOut->patient->fullname }}</td>
                            </tr>
                            @endif
                            @if($stockOut->out_type == 'department' && $stockOut->department)
                            <tr>
                                <td><strong>{{ __('inventory.department') }}</strong></td>
                                <td>{{ $stockOut->department }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td><strong>{{ __('inventory.status') }}</strong></td>
                                <td>
                                    @if($stockOut->status == 'draft')
                                        <span class="badge badge-secondary">{{ __('inventory.status_draft') }}</span>
                                    @elseif($stockOut->status == 'confirmed')
                                        <span class="badge badge-success">{{ __('inventory.status_confirmed') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('inventory.status_cancelled') }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>{{ __('inventory.notes') }}</strong></td>
                                <td>{{ $stockOut->notes ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.total_amount') }}</strong></td>
                                <td><strong>{{ number_format($stockOut->total_amount, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.added_by') }}</strong></td>
                                <td>{{ $stockOut->addedBy ? $stockOut->addedBy->othername : '-' }}</td>
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
                        <th>{{ __('inventory.unit_cost') }}</th>
                        <th>{{ __('inventory.amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($stockOut->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->inventoryItem->item_code ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->name ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->specification ?? '-' }}</td>
                        <td>{{ $item->inventoryItem->unit ?? '-' }}</td>
                        <td>{{ number_format($item->qty, 2) }}</td>
                        <td>{{ number_format($item->unit_cost, 2) }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="7" class="text-right"><strong>{{ __('inventory.total_amount') }}</strong></td>
                        <td><strong>{{ number_format($stockOut->total_amount, 2) }}</strong></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
