@extends('printer_pdf.layout')
@section('content')
@php
    // 与 refunds/create 的下拉选项一一对应；库里存英文枚举值，直接输出会露出 cash 这种原始值
    $methodLabels = [
        'cash'         => __('invoices.cash'),
        'wechat'       => __('invoices.wechat_pay'),
        'alipay'       => __('invoices.alipay'),
        'card'         => __('invoices.bank_card'),
        'stored_value' => __('invoices.stored_value'),
    ];
    // 模型的 datetime cast 只作用于 toArray()，直接 echo Carbon 会带出秒
    $dateTime = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '-';
@endphp
    <style type="text/css">
        .refund-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0 18px 0;
        }

        .meta-table td {
            font-size: 13px;
            padding: 3px 0;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #b0b0b0;
            padding: 7px 10px;
            font-size: 13px;
            text-align: left;
        }

        .detail-table th {
            width: 30%;
            background: #f2f2f2;
            font-weight: bold;
        }

        .amount-cell {
            font-size: 16px;
            font-weight: bold;
        }

        .sign-table {
            width: 100%;
            margin-top: 40px;
        }

        .sign-table td {
            font-size: 13px;
            padding-top: 24px;
        }
    </style>

    <div class="refund-title">{{ __('invoices.refund_receipt') }}</div>

    <table width="100%" class="meta-table">
        <tr>
            <td align="left">{{ __('invoices.refund_no') }}: {{ $refund->refund_no }}</td>
            <td align="right">{{ __('invoices.refund_date') }}: {{ $dateTime($refund->refund_date ?: $refund->created_at) }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <tr>
            <th>{{ __('patient.name') }}</th>
            <td>{{ $refund->patient->full_name ?? '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('patient.phone') }}</th>
            <td>{{ $refund->patient->phone_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.invoice_no') }}</th>
            <td>{{ $refund->invoice->invoice_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.refund_amount') }}</th>
            <td class="amount-cell">{{ number_format((float) $refund->refund_amount, 2) }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.refund_method') }}</th>
            <td>{{ $methodLabels[$refund->refund_method] ?? ($refund->refund_method ?: '-') }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.refund_reason') }}</th>
            <td>{{ $refund->refund_reason ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.requested_by') }}</th>
            <td>{{ $refund->whoAdded ? $refund->whoAdded->surname . $refund->whoAdded->othername : '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('invoices.approved_by') }}</th>
            <td>
                {{ $refund->approvedBy ? $refund->approvedBy->surname . $refund->approvedBy->othername : '-' }}
                @if($refund->approved_at)
                    ({{ $dateTime($refund->approved_at) }})
                @endif
            </td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td align="left">{{ __('print.received_by') }}: ____________________</td>
            <td align="right">{{ __('print.issued_by') }}: ____________________</td>
        </tr>
    </table>
@endsection
