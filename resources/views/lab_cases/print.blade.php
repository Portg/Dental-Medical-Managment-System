@extends('printer_pdf.layout')
@section('content')
    <style type="text/css">
        .text-alignment { text-align: right; margin-right: 20px; }
        .info-table td { padding: 4px 8px; }
        .info-label { font-weight: bold; width: 120px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px 0; border-bottom: 1px solid #b0b0b0; padding-bottom: 4px; }
    </style>

    <div class="row">
        {{-- Header --}}
        <table width="100%">
            <tr>
                <td align="left">
                    <span style="font-size: 16px; font-weight: bold;">{{ __('lab_cases.lab_case') }}</span><br>
                    <span>{{ __('lab_cases.lab_case_no') }}: <strong>{{ $labCase->lab_case_no }}</strong></span><br>
                    <span>{{ __('lab_cases.created_at') }}: {{ $labCase->created_at ? $labCase->created_at->format('Y-m-d') : '-' }}</span>
                </td>
                <td align="right">
                    <span>{{ __('lab_cases.status') }}:
                        <strong>{{ __('lab_cases.status_' . $labCase->status) }}</strong>
                    </span>
                </td>
            </tr>
        </table>

        <hr style="border: 1px solid #b0b0b0">

        {{-- Patient & Doctor Info --}}
        <div class="section-title">{{ __('lab_cases.patient') }} / {{ __('lab_cases.doctor') }}</div>
        <table width="100%" class="info-table">
            <tr>
                <td class="info-label">{{ __('lab_cases.patient') }}:</td>
                <td>
                    @if($labCase->patient)
                        {{ \App\Http\Helper\NameHelper::join($labCase->patient->surname, $labCase->patient->othername) }}
                        @if($labCase->patient->phone_no)
                            ({{ $labCase->patient->phone_no }})
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="info-label">{{ __('lab_cases.doctor') }}:</td>
                <td>{{ $labCase->doctor->name ?? '-' }}</td>
            </tr>
        </table>

        {{-- Lab Info --}}
        <div class="section-title">{{ __('lab_cases.lab') }}</div>
        <table width="100%" class="info-table">
            <tr>
                <td class="info-label">{{ __('lab_cases.lab_name') }}:</td>
                <td>{{ $labCase->lab->name ?? '-' }}</td>
                <td class="info-label">{{ __('lab_cases.phone') }}:</td>
                <td>{{ $labCase->lab->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">{{ __('lab_cases.contact') }}:</td>
                <td>{{ $labCase->lab->contact ?? '-' }}</td>
                <td class="info-label">{{ __('lab_cases.address') }}:</td>
                <td>{{ $labCase->lab->address ?? '-' }}</td>
            </tr>
        </table>

        {{-- Case Details --}}
        <div class="section-title">{{ __('lab_cases.lab_case_details') }}</div>
        <table width="100%" class="info-table">
            <tr>
                <td class="info-label">{{ __('lab_cases.prosthesis_type') }}:</td>
                <td>{{ __('lab_cases.type_' . $labCase->prosthesis_type) }}</td>
                <td class="info-label">{{ __('lab_cases.material') }}:</td>
                <td>{{ $labCase->material ? __('lab_cases.material_' . $labCase->material) : '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">{{ __('lab_cases.color_shade') }}:</td>
                <td>{{ $labCase->color_shade ?? '-' }}</td>
                <td class="info-label">{{ __('lab_cases.teeth_positions') }}:</td>
                <td>
                    @if($labCase->teeth_positions)
                        {{ is_array($labCase->teeth_positions) ? implode(', ', $labCase->teeth_positions) : $labCase->teeth_positions }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>

        @if($labCase->special_requirements)
            <table width="100%" class="info-table" style="margin-top: 5px;">
                <tr>
                    <td class="info-label">{{ __('lab_cases.special_requirements') }}:</td>
                    <td>{{ $labCase->special_requirements }}</td>
                </tr>
            </table>
        @endif

        @if($labCase->notes)
            <table width="100%" class="info-table">
                <tr>
                    <td class="info-label">{{ __('lab_cases.notes') }}:</td>
                    <td>{{ $labCase->notes }}</td>
                </tr>
            </table>
        @endif

        {{-- Dates & Fees --}}
        <div class="section-title">{{ __('lab_cases.sent_date') }} / {{ __('lab_cases.lab_fee') }}</div>
        <table width="100%" class="info-table">
            <tr>
                <td class="info-label">{{ __('lab_cases.sent_date') }}:</td>
                <td>{{ $labCase->sent_date ?? '-' }}</td>
                <td class="info-label">{{ __('lab_cases.expected_return_date') }}:</td>
                <td>{{ $labCase->expected_return_date ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">{{ __('lab_cases.actual_return_date') }}:</td>
                <td>{{ $labCase->actual_return_date ?? '-' }}</td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table width="100%" style="margin-top: 10px;">
            <tr>
                <td style="width:50%">
                    <table width="100%">
                        <tr>
                            <td class="info-label">{{ __('lab_cases.lab_fee') }}:</td>
                            <td>{{ $labCase->lab_fee ? number_format($labCase->lab_fee, 2) : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="info-label">{{ __('lab_cases.patient_charge') }}:</td>
                            <td>{{ $labCase->patient_charge ? number_format($labCase->patient_charge, 2) : '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%">
                    @if($labCase->rework_count > 0)
                        <table width="100%">
                            <tr>
                                <td class="info-label">{{ __('lab_cases.rework_count') }}:</td>
                                <td>{{ $labCase->rework_count }}</td>
                            </tr>
                            @if($labCase->rework_reason)
                                <tr>
                                    <td class="info-label">{{ __('lab_cases.rework_reason') }}:</td>
                                    <td>{{ $labCase->rework_reason }}</td>
                                </tr>
                            @endif
                        </table>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Signature Area --}}
        <table width="100%" style="margin-top: 40px;">
            <tr>
                <td style="width:50%">
                    <span>{{ __('lab_cases.doctor') }}: ___________________</span>
                </td>
                <td style="width:50%; text-align:right;">
                    <span>{{ __('lab_cases.contact') }}: ___________________</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
