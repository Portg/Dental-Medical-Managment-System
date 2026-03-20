@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/requisition.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">
                        {{ __('inventory.requisitions') }} - {{ $stockOut->stock_out_no }}
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('requisitions') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                    </a>
                    @can('request-inventory')
                        @if($stockOut->isDraft() && $stockOut->_who_added == Auth::id())
                            <a href="{{ url('requisitions/' . $stockOut->id . '/edit') }}" class="btn btn-primary">
                                <i class="fa fa-edit"></i> {{ __('common.edit') }}
                            </a>
                            <button type="button" class="btn btn-warning" onclick="submitRequisition({{ $stockOut->id }})">
                                <i class="fa fa-paper-plane"></i> {{ __('inventory.submit_for_approval') }}
                            </button>
                        @endif
                        @if($stockOut->isRejected() && $stockOut->_who_added == Auth::id())
                            <button type="button" class="btn btn-default" onclick="cloneRequisition({{ $stockOut->id }})">
                                <i class="fa fa-copy"></i> {{ __('inventory.reapply') }}
                            </button>
                        @endif
                    @endcan
                    @can('manage-inventory')
                        @if($stockOut->isPendingApproval())
                            <button type="button" class="btn btn-success" onclick="approveRequisition({{ $stockOut->id }})">
                                <i class="fa fa-check"></i> {{ __('inventory.approve') }}
                            </button>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                                <i class="fa fa-times"></i> {{ __('inventory.reject') }}
                            </button>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="portlet-body">
                {{-- 基本信息 --}}
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>{{ __('inventory.stock_out_no') }}</strong></td>
                                <td>{{ $stockOut->stock_out_no }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.requisition_date') }}</strong></td>
                                <td>{{ $stockOut->stock_out_date->format('Y-m-d') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.recipient') }}</strong></td>
                                <td>{{ $stockOut->recipient ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.department') }}</strong></td>
                                <td>{{ $stockOut->department ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.status') }}</strong></td>
                                <td>
                                    @php
                                        $badgeMap = [
                                            'draft'            => 'badge-secondary',
                                            'pending_approval' => 'badge-warning',
                                            'confirmed'        => 'badge-success',
                                            'rejected'         => 'badge-danger',
                                            'cancelled'        => 'badge-default',
                                        ];
                                        $badgeClass = $badgeMap[$stockOut->status] ?? 'badge-default';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $stockOut->status_label }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>{{ __('inventory.added_by') }}</strong></td>
                                <td>{{ $stockOut->addedBy ? $stockOut->addedBy->othername : '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.created_at') }}</strong></td>
                                <td>{{ $stockOut->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('inventory.notes') }}</strong></td>
                                <td>{{ $stockOut->notes ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- 审批信息 --}}
                @if($stockOut->approved_by)
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert {{ $stockOut->isConfirmed() ? 'alert-success' : 'alert-danger' }}">
                            <strong>{{ __('inventory.approval_info') }}</strong>
                            &nbsp;&nbsp;
                            {{ __('inventory.approved_by') }}: {{ $stockOut->approvedBy ? $stockOut->approvedBy->othername : '-' }}
                            &nbsp;&nbsp;
                            {{ __('inventory.approved_at') }}: {{ $stockOut->approved_at ? $stockOut->approved_at->format('Y-m-d H:i') : '-' }}
                        </div>
                    </div>
                </div>
                @endif

                <hr>
                {{-- 物品明细 --}}
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
                            <td>{{ (int) $item->qty }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 驳回 Modal --}}
@can('manage-inventory')
@if($stockOut->isPendingApproval())
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">{{ __('inventory.reject') }} - {{ $stockOut->stock_out_no }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('inventory.rejection_reason') }}</label>
                    <textarea id="rejection-reason" class="form-control" rows="3" placeholder="{{ __('inventory.rejection_reason') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="doReject({{ $stockOut->id }})">{{ __('inventory.reject') }}</button>
            </div>
        </div>
    </div>
</div>
@endif
@endcan

@endsection

@section('js')
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.RequisitionShowConfig = { csrfToken: '{{ csrf_token() }}' };
    </script>
    <script src="{{ asset('include_js/requisition_show.js') }}?v={{ filemtime(public_path('include_js/requisition_show.js')) }}" type="text/javascript"></script>
@endsection
