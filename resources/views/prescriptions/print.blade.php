@extends('printer_pdf.layout')
@section('content')

<div class="row">
    <div class="col-xs-12">
        <table width="100%">
            <tr>
                <td align="left">
                    <span>{{ __('prescriptions.prescribed_by') }}: {{ $doctor ? $doctor->surname . $doctor->othername : '-' }}</span>
                </td>
                <td align="center">
                    <span>{{ __('prescriptions.prescription_no') }}: {{ $prescription->prescription_no }}</span>
                </td>
                <td align="right">
                    <span>{{ $patient->full_name }} ({{ $patient->patient_no }})</span>
                </td>
            </tr>
            <tr>
                <td colspan="3" align="left">
                    <span>{{ __('prescriptions.prescription_date') }}: {{ $prescription->prescription_date?->format('Y-m-d') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="col-xs-12">
        <h3>{{ __('prescriptions.prescription') }}</h3>
        <table width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('prescriptions.drug_name') }}</th>
                    <th>{{ __('prescriptions.dosage') }}</th>
                    <th>{{ __('prescriptions.quantity') }}</th>
                    <th>{{ __('prescriptions.unit_price') }}</th>
                    <th>{{ __('prescriptions.amount') }}</th>
                    <th>{{ __('prescriptions.frequency') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $total = '0.00'; @endphp
                @foreach($prescription->items as $i => $item)
                    @php
                        $lineAmount = bcmul((string) ($item->unit_price ?? 0), (string) $item->quantity, 2);
                        $total = bcadd($total, $lineAmount, 2);
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->drug_name ?: ($item->medicalService?->name ?? '-') }}</td>
                        <td>{{ $item->dosage ?? '-' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price ?? 0, 2) }}</td>
                        <td>{{ number_format($lineAmount, 2) }}</td>
                        <td>{{ $item->frequency ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" align="right"><strong>{{ __('prescriptions.total_amount') }}:</strong></td>
                    <td><strong>{{ number_format($total, 2) }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @if($prescription->notes)
            <p><strong>{{ __('prescriptions.notes') }}:</strong> {{ $prescription->notes }}</p>
        @endif
    </div>
</div>

@endsection
