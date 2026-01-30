@extends('printer_pdf.layout')
@section('content')
    <style type="text/css">
        .standout {
            /*font-weight:bold;*/
        }

        .text-alignment {
            text-align: right;
            margin-right: 40px;
        }

    </style>

    <div class="row">
        <table width="100%">
            <tr>
                <td align="left">
                    <span>{{ __('invoices.invoice_no') }}: {{ $invoice->invoice_no }} <br>
                        {{ __('invoices.date') }}: {{ $invoice->created_at }}<br>
                    </span>

                </td>
                <td align="center">
                </td>
                <td align="right">
                    <span>{{ $patient->surname." ".$patient->othername }}
                    </span>
                </td>
            </tr>
        </table>
        <div class="col-xs-4">
            <table width="100%">
                <thead>
                <tr>
                    <th>{{ __('invoices.transaction') }}</th>
                    <th class="text-alignment">{{ __('invoices.quantity') }}</th>
                    <th class="text-alignment">{{ __('invoices.unit_price') }}</th>
                    <th class="text-alignment">{{ __('invoices.total_amount') }}</th>
                </tr>
                </thead>
                <tbody>
                @php $due_amount=0; @endphp
                @foreach($invoice_items as $row)
                    <tr>
                        <td>{{ $row->medical_service->name." ".$row->tooth_no }}</td>
                        <td class="text-alignment">{{ $row->qty }}</td>
                        <td class="text-alignment">{{ number_format($row->price) }}</td>
                        <td class="text-alignment">{{ number_format($row->qty*$row->price) }}</td>
                    </tr>
                    @php /** @var TYPE_NAME $due_amount */
                       $due_amount+=$row->qty*$row->price; @endphp
                @endforeach

                </tbody>
                <tfoot>
                <tr>
                    <hr style="border: 1px solid #b0b0b0">
                    <td class="standout" style="color: red">{{ __('invoices.total_amount') }}</td>
                    <td></td>
                    <td></td>
                    <td class="standout text-alignment" style="color: red">{{ number_format($due_amount) }}</td>
                </tr>

                </tfoot>
            </table>
        </div>
        <div class="col-xs-4">

            <h3 style="font-size: 15px;">{{ __('invoices.this_receipt') }}</h3>
            <table width="100%">
                <thead>
                <tr>
                    <th>{{ __('invoices.payment_date') }}</th>
                    <th>{{ __('invoices.payment_method') }}</th>
                    <th class="text-alignment">{{ __('invoices.amount') }}</th>
                </tr>
                </thead>
                <tbody>
                @php $paid_amount=0; @endphp
                @foreach($payments as $row)
                    <tr>
                        <td>{{ $row->payment_date }}</td>
                        <td>{{ $row->payment_method }}</td>
                        <td class="text-alignment">{{ number_format($row->amount) }}</td>
                    </tr>
                    @php /** @var TYPE_NAME $paid_amount */
                       $paid_amount+=$row->amount; @endphp
                @endforeach

                </tbody>
                <tfoot>
                <tr>
                    <td class="standout" style="color: red">{{ __('invoices.total_paid_amount') }}</td>
                    <td></td>
                    <td class="standout text-alignment" style="color: red">{{ number_format($paid_amount) }}
                    </td>
                </tr>
                <tr>
                    <td class="standout" style="color: red">{{ __('invoices.outstanding_balance') }}</td>
                    <td></td>
                    <td class="standout text-alignment" style="color: red">{{ number_format($due_amount-$paid_amount) }}
                    </td>
                </tr>


                </tfoot>
            </table>
        </div>

    </div>
@endsection
