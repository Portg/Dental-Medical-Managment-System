@extends('printer_pdf.layout')
@section('content')
    <style type="text/css">
        .case-info {
            font-family: sans-serif;
            font-size: 12px;
        }
        .case-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .case-info td, .case-info th {
            padding: 5px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .case-info th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            padding: 5px;
            background-color: #e8e8e8;
            border-left: 4px solid #3498db;
        }
        .soap-section {
            margin-bottom: 15px;
        }
        .soap-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .soap-content {
            padding: 8px;
            background-color: #fafafa;
            border: 1px solid #eee;
            min-height: 30px;
        }
        .diagnosis-item {
            padding: 5px 0;
            border-bottom: 1px dashed #ddd;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            color: white;
        }
        .status-open { background-color: #27ae60; }
        .status-closed { background-color: #e74c3c; }
        .status-followup { background-color: #f39c12; }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        .teeth-list {
            background-color: #f9f9f9;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
        }
    </style>

    <div class="case-info">
        <div class="print-header">
            <h3>{{ __('medical_cases.medical_record') }}</h3>
            <p style="font-size: 11px; color: #666;">{{ __('print.printed_at') }}: {{ now()->format('Y-m-d H:i') }}</p>
        </div>

        <!-- Basic Information -->
        <table>
            <tr>
                <th width="15%">{{ __('medical_cases.case_no') }}</th>
                <td width="35%">{{ $case->case_no }}</td>
                <th width="15%">{{ __('medical_cases.case_date') }}</th>
                <td width="35%">{{ $case->case_date }}</td>
            </tr>
            <tr>
                <th>{{ __('medical_cases.patient') }}</th>
                <td>{{ $case->patient->full_name ({{ $case->patient->patient_no }})</td>
                <th>{{ __('medical_cases.status') }}</th>
                <td>
                    <span class="status-badge status-{{ strtolower(str_replace('-', '', $case->status)) }}">
                        {{ __('medical_cases.status_' . strtolower(str_replace('-', '_', $case->status))) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>{{ __('medical_cases.doctor') }}</th>
                <td>{{ $case->doctor ? $case->doctor->full_name : '-' }}</td>
                <th>{{ __('medical_cases.visit_type') }}</th>
                <td>{{ __('medical_cases.visit_type_' . $case->visit_type) }}</td>
            </tr>
            @if($case->patient->hasAllergies())
            <tr>
                <th>{{ __('patient.allergies') }}</th>
                <td colspan="3" style="color: #e74c3c; font-weight: bold;">
                    {{ $case->patient->allergies_display }}
                </td>
            </tr>
            @endif
        </table>

        <!-- SOAP Records -->
        <div class="section-title">{{ __('medical_cases.soap_section') }}</div>

        <div class="soap-section">
            <div class="soap-label">S - {{ __('medical_cases.chief_complaint') }}</div>
            <div class="soap-content">{{ $case->chief_complaint ?: '-' }}</div>
        </div>

        @if($case->history_of_present_illness)
        <div class="soap-section">
            <div class="soap-label">{{ __('medical_cases.history_of_present_illness') }}</div>
            <div class="soap-content">{{ $case->history_of_present_illness }}</div>
        </div>
        @endif

        <div class="soap-section">
            <div class="soap-label">O - {{ __('medical_cases.examination') }}</div>
            <div class="soap-content">
                {{ $case->examination ?: '-' }}
                @if($case->examination_teeth && count($case->examination_teeth) > 0)
                    <div class="teeth-list">
                        <strong>{{ __('medical_cases.examination_teeth') }}:</strong>
                        {{ implode(', ', $case->examination_teeth) }}
                    </div>
                @endif
            </div>
        </div>

        @if($case->auxiliary_examination)
        <div class="soap-section">
            <div class="soap-label">{{ __('medical_cases.auxiliary_examination') }}</div>
            <div class="soap-content">{{ $case->auxiliary_examination }}</div>
        </div>
        @endif

        <div class="soap-section">
            <div class="soap-label">A - {{ __('medical_cases.diagnosis') }}</div>
            <div class="soap-content">
                {{ $case->diagnosis ?: '-' }}
                @if($case->diagnosis_code)
                    <br><small>ICD-10: {{ $case->diagnosis_code }}</small>
                @endif
                @if($case->related_teeth && count($case->related_teeth) > 0)
                    <div class="teeth-list">
                        <strong>{{ __('medical_cases.related_teeth') }}:</strong>
                        {{ implode(', ', $case->related_teeth) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="soap-section">
            <div class="soap-label">P - {{ __('medical_cases.treatment') }}</div>
            <div class="soap-content">{{ $case->treatment ?: '-' }}</div>
        </div>

        @if($case->medical_orders)
        <div class="soap-section">
            <div class="soap-label">{{ __('medical_cases.medical_orders') }}</div>
            <div class="soap-content">{{ $case->medical_orders }}</div>
        </div>
        @endif

        <!-- Diagnoses List -->
        @if($diagnoses && $diagnoses->count() > 0)
        <div class="section-title">{{ __('medical_cases.diagnoses') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('medical_cases.diagnosis_name') }}</th>
                    <th>{{ __('medical_cases.icd_code') }}</th>
                    <th>{{ __('medical_cases.severity') }}</th>
                    <th>{{ __('medical_cases.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($diagnoses as $diagnosis)
                <tr>
                    <td>{{ $diagnosis->diagnosis_name }}</td>
                    <td>{{ $diagnosis->icd_code ?: '-' }}</td>
                    <td>{{ __('medical_cases.severity_' . strtolower($diagnosis->severity)) }}</td>
                    <td>{{ __('medical_cases.diagnosis_status_' . strtolower($diagnosis->status)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Treatment Plans -->
        @if($treatmentPlans && $treatmentPlans->count() > 0)
        <div class="section-title">{{ __('medical_cases.treatment_plans') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('medical_cases.plan_name') }}</th>
                    <th>{{ __('medical_cases.priority') }}</th>
                    <th>{{ __('medical_cases.estimated_cost') }}</th>
                    <th>{{ __('medical_cases.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($treatmentPlans as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td>{{ __('medical_cases.priority_' . strtolower($plan->priority)) }}</td>
                    <td>{{ number_format($plan->estimated_cost, 2) }}</td>
                    <td>{{ __('medical_cases.plan_status_' . strtolower(str_replace(' ', '_', $plan->status))) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Latest Vital Signs -->
        @if($latestVitalSign)
        <div class="section-title">{{ __('medical_cases.vital_signs') }}</div>
        <table>
            <tr>
                <th>{{ __('medical_cases.blood_pressure') }}</th>
                <td>{{ $latestVitalSign->blood_pressure_formatted }}</td>
                <th>{{ __('medical_cases.heart_rate') }}</th>
                <td>{{ $latestVitalSign->heart_rate }} bpm</td>
            </tr>
            <tr>
                <th>{{ __('medical_cases.temperature') }}</th>
                <td>{{ $latestVitalSign->temperature }}°C</td>
                <th>{{ __('medical_cases.respiratory_rate') }}</th>
                <td>{{ $latestVitalSign->respiratory_rate ?: '-' }} /min</td>
            </tr>
            @if($latestVitalSign->weight || $latestVitalSign->height)
            <tr>
                <th>{{ __('medical_cases.weight') }}</th>
                <td>{{ $latestVitalSign->weight ? $latestVitalSign->weight . ' kg' : '-' }}</td>
                <th>{{ __('medical_cases.bmi') }}</th>
                <td>{{ $latestVitalSign->bmi ?: '-' }}</td>
            </tr>
            @endif
        </table>
        @endif

        <!-- Next Visit Info -->
        @if($case->next_visit_date)
        <div class="section-title">{{ __('medical_cases.next_visit') }}</div>
        <table>
            <tr>
                <th>{{ __('medical_cases.next_visit_date') }}</th>
                <td>{{ $case->next_visit_date }}</td>
            </tr>
            @if($case->next_visit_note)
            <tr>
                <th>{{ __('medical_cases.next_visit_note') }}</th>
                <td>{{ $case->next_visit_note }}</td>
            </tr>
            @endif
        </table>
        @endif

        <!-- Signature Section -->
        <div style="margin-top: 40px;">
            <table>
                <tr>
                    <td width="50%" style="border: none; text-align: center;">
                        <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                            {{ __('medical_cases.doctor_signature') }}
                        </div>
                    </td>
                    <td width="50%" style="border: none; text-align: center;">
                        <div style="border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px;">
                            {{ __('medical_cases.date') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
@endsection