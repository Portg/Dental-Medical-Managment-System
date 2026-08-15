@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/refund-detail.css') }}?v={{ filemtime(public_path('css/refund-detail.css')) }}">
@endsection

@php
    $statusLabels = [
        \App\Refund::APPROVAL_PENDING  => __('invoices.refund_pending'),
        \App\Refund::APPROVAL_APPROVED => __('invoices.refund_approved'),
        \App\Refund::APPROVAL_REJECTED => __('invoices.refund_rejected'),
    ];
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-action-undo"></i>
                    <span class="caption-subject">{{ __('invoices.refund_detail') }}</span>
                </div>
                <div class="actions">
                    @if($refund->approval_status === \App\Refund::APPROVAL_APPROVED)
                        <a href="{{ url('refunds/' . $refund->id . '/print') }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="icon-printer"></i> {{ __('invoices.print_refund') }}
                        </a>
                    @endif
                    <a href="{{ url('refunds') }}" class="btn btn-sm btn-default">
                        <i class="icon-arrow-left"></i> {{ __('invoices.back_to_refunds') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="refund-summary">
                    <div class="refund-no">{{ $refund->refund_no }}</div>
                    <div class="refund-amount">-{{ number_format((float) $refund->refund_amount, 2) }}</div>
                    <span class="refund-status {{ $refund->approval_status }}">
                        {{ $statusLabels[$refund->approval_status] ?? $refund->approval_status }}
                    </span>
                </div>

                @if($refund->approval_status === \App\Refund::APPROVAL_REJECTED && $refund->rejection_reason)
                    <div class="alert alert-danger refund-alert">
                        <strong>{{ __('invoices.rejection_reason') }}：</strong>{{ $refund->rejection_reason }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-block">
                            <div class="detail-block-title">{{ __('invoices.refund') }}</div>
                            <table class="table table-detail">
                                <tr>
                                    <th>{{ __('patient.name') }}</th>
                                    <td>{{ $refund->patient->full_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('patient.phone') }}</th>
                                    <td>{{ $refund->patient->phone_no ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.refund_amount') }}</th>
                                    <td>{{ number_format((float) $refund->refund_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.refund_method') }}</th>
                                    <td>{{ $refund->refund_method ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.refund_date') }}</th>
                                    <td>{{ $refund->refund_date ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.refund_reason') }}</th>
                                    <td>{{ $refund->refund_reason ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="detail-block">
                            <div class="detail-block-title">{{ __('invoices.invoice') }}</div>
                            <table class="table table-detail">
                                <tr>
                                    <th>{{ __('invoices.invoice_no') }}</th>
                                    <td>
                                        @if($refund->invoice)
                                            <a href="{{ url('invoices/' . $refund->invoice->id) }}">{{ $refund->invoice->invoice_no }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.total_amount') }}</th>
                                    <td>{{ $refund->invoice ? number_format((float) $refund->invoice->total_amount, 2) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.paid_amount') }}</th>
                                    <td>{{ $refund->invoice ? number_format((float) $refund->invoice->paid_amount, 2) : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.requested_by') }}</th>
                                    <td>{{ $refund->whoAdded ? $refund->whoAdded->surname . $refund->whoAdded->othername : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.requested_at') }}</th>
                                    <td>{{ $refund->created_at ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('invoices.approved_by') }}</th>
                                    <td>
                                        @if($refund->approvedBy)
                                            {{ $refund->approvedBy->surname . $refund->approvedBy->othername }}
                                            <span class="approved-at">{{ $refund->approved_at }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                @if($refund->invoice && $refund->invoice->items->isNotEmpty())
                    <div class="detail-block">
                        <div class="detail-block-title">{{ __('invoices.invoice_items') }}</div>
                        <table class="table table-bordered table-striped table-items">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('invoices.service') }}</th>
                                    <th class="text-right">{{ __('invoices.quantity') }}</th>
                                    <th class="text-right">{{ __('invoices.unit_price') }}</th>
                                    <th class="text-right">{{ __('invoices.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($refund->invoice->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->medicalService->name ?? '-' }}</td>
                                        <td class="text-right">{{ $item->qty }}</td>
                                        <td class="text-right">{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="text-right">{{ number_format((float) ($item->discounted_price ?? $item->price), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
