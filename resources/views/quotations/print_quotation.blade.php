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
                    <span>{{ __('quotations.quotation_no') }}: {{ $quotation->quotation_no }} <br>
                        {{ __('quotations.date') }}: {{ $quotation->created_at }}<br>
                    </span>

                </td>
                <td align="center">
                </td>
                <td>
                    <span style="font-weight: bold">{{ $patient->full_name }}
                    </span>
                </td>
            </tr>
        </table>
        {{--        <div class="col-xs-4">--}}
        <table width="100%">
            <thead>
            <tr>
                <th style="  text-align: left">{{ __('quotations.procedure') }}</th>
                <th class="text-alignment">{{ __('quotations.qty') }}</th>
                <th class="text-alignment">{{ __('quotations.price') }}</th>
                <th class="text-alignment">{{ __('quotations.total_amount') }}</th>
            </tr>
            </thead>
            <tbody>
            {{-- quotation_items 的金额列叫 amount，存的是单价；行小计 = qty * amount。
                 原先读 $row->price（该列不存在）导致打印出来价格与总额全是 0。 --}}
            @php $due_amount=0; @endphp
            @foreach($quotation_items as $row)
                <tr>
                    {{-- quotation_items 上没有 tooth_no 列（模板是照着一个比实际更丰富的
                         模型写的），直接取会抛 Undefined property。留着拼接以便将来补列。 --}}
                    <td>{{ trim($row->name . ' ' . ($row->tooth_no ?? '')) }}</td>
                    <td class="text-alignment">{{ number_format($row->qty) }}</td>
                    <td class="text-alignment">{{ number_format($row->amount) }}</td>
                    <td class="text-alignment">{{ number_format($row->qty * $row->amount) }}</td>
                </tr>
                @php /** @var TYPE_NAME $due_amount */
                       $due_amount += $row->qty * $row->amount; @endphp
            @endforeach

            </tbody>
            <tfoot>
            <td class="standout">{{ __('quotations.total_amount') }}</td>
            <td></td>
            <td></td>
            <td class="standout text-alignment">{{ number_format($due_amount) }}</td>
            </tfoot>
        </table>
        {{--        </div>--}}
        <div class="col-xs-4">

        </div>
    </div>
@endsection
