@extends(\App\Http\Helper\FunctionsHelper::navigation())
@php use App\Services\DataMaskingService; @endphp
@section('css')
    @include('layouts.page_loader')
    <style>
        /* ── Patient Detail Three-Zone Layout ── */
        .patient-summary-bar {
            background: #fff;
            border: 1px solid #e7ecf1;
            border-radius: 4px;
            padding: 12px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .patient-summary-bar .summary-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        .patient-summary-bar .summary-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-right: 6px;
        }
        .patient-summary-bar .summary-tag {
            display: inline-block;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 3px;
            margin-right: 6px;
            color: #fff;
        }
        .patient-summary-bar .summary-divider {
            width: 1px;
            height: 20px;
            background: #ddd;
            margin: 0 10px;
        }
        .patient-summary-bar .summary-item {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
        }
        .patient-summary-bar .summary-item strong {
            color: #333;
        }
        .patient-summary-bar .summary-item .amount {
            color: #e74c3c;
            font-weight: 600;
        }
        .patient-summary-bar .summary-actions {
            margin-left: auto;
            display: flex;
            gap: 6px;
        }

        /* Left Panel */
        .patient-left-panel {
            background: #fff;
            border: 1px solid #e7ecf1;
            border-radius: 4px;
            padding: 20px 15px;
            position: sticky;
            top: 10px;
        }
        .patient-left-panel .avatar-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .patient-left-panel .avatar-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e7ecf1;
        }
        .patient-left-panel .avatar-section .patient-name {
            display: block;
            font-size: 15px;
            font-weight: 600;
            margin-top: 8px;
            color: #333;
        }
        .patient-left-panel .avatar-section .patient-group-badge {
            display: inline-block;
            margin-top: 4px;
        }
        .patient-left-panel .panel-section {
            margin-bottom: 18px;
        }
        .patient-left-panel .panel-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #f0f0f0;
        }
        .patient-left-panel .tag-checkbox,
        .patient-left-panel .group-radio {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            color: #555;
            cursor: pointer;
        }
        .patient-left-panel .tag-checkbox input,
        .patient-left-panel .group-radio input {
            margin-right: 6px;
        }
        .patient-left-panel .panel-actions {
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 6px;
        }
        .patient-left-panel .panel-actions .btn {
            flex: 1;
            font-size: 12px;
        }

        /* Right content area */
        .patient-right-content .portlet {
            margin-bottom: 0;
        }
        .patient-right-content .nav-tabs > li > a {
            padding: 8px 14px;
            font-size: 13px;
        }
    </style>
@endsection

@section('content')

{{-- ═══════════════ Summary Bar (Top) ═══════════════ --}}
<div class="patient-summary-bar">
    @if($patient->photo)
        <img src="{{ asset('storage/' . $patient->photo) }}" class="summary-avatar">
    @else
        <img src="{{ asset('images/default-avatar.png') }}" class="summary-avatar">
    @endif

    <span class="summary-name pii-field" data-field="full_name">{{ DataMaskingService::maskName($patient->full_name) }}</span>

    @if($patient->member_status === 'Active' && $patient->memberLevel)
        <span class="summary-tag" style="background:#3598dc;">{{ $patient->memberLevel->name }}</span>
    @endif

    <div class="summary-divider"></div>

    <span class="summary-item">{{ $patient->patient_no }}</span>

    <div class="summary-divider"></div>

    <span class="summary-item">
        <span class="pii-field" data-field="phone_no">{{ DataMaskingService::maskPhone($patient->phone_no) ?? '-' }}</span>
    </span>

    <div class="summary-divider"></div>

    <span class="summary-item">
        {{ __('patient.first_visit_doctor') }}:
        @if($firstVisit)
            <strong>{{ $firstVisit->doctor->name ?? '-' }}</strong>/{{ $firstVisit->start_date?->format('Y-m-d') }}
        @else
            {{ __('patient.no_visit_record') }}
        @endif
    </span>

    <div class="summary-divider"></div>

    <span class="summary-item">
        {{ __('patient.latest_visit') }}:
        @if($latestVisit)
            <strong>{{ $latestVisit->doctor->name ?? '-' }}</strong>/{{ $latestVisit->start_date?->format('Y-m-d') }}
        @else
            -
        @endif
    </span>

    <div class="summary-divider"></div>

    <span class="summary-item">
        {{ __('patient.total_spending') }}: <span class="amount">&yen;{{ number_format($totalSpending, 2) }}</span>
    </span>

    @if($patient->member_status === 'Active')
        <div class="summary-divider"></div>
        <span class="summary-item">
            {{ __('patient.member_balance') }}: <span class="amount">&yen;{{ number_format($patient->member_balance ?? 0, 2) }}</span>
        </span>
    @endif

    @can('view-sensitive-data')
    <div class="summary-actions">
        <button id="revealPiiBtn" class="btn btn-xs btn-default">
            <i class="fa fa-eye"></i> {{ __('data_security.reveal_sensitive') }}
        </button>
    </div>
    @endcan
</div>

{{-- ═══════════════ Left Panel + Right Content ═══════════════ --}}
<input type="hidden" id="global_patient_id" value="{{ $patient->id }}">

<div class="row">
    {{-- Left Panel (col-md-2) --}}
    <div class="col-md-2">
        <div class="patient-left-panel">
            {{-- Avatar --}}
            <div class="avatar-section">
                @if($patient->photo)
                    <img src="{{ asset('storage/' . $patient->photo) }}" alt="">
                @else
                    <img src="{{ asset('images/default-avatar.png') }}" alt="">
                @endif
                <span class="patient-name pii-field" data-field="full_name_summary">{{ DataMaskingService::maskName($patient->full_name) }}</span>
                @if($patient->patient_group)
                    <div class="patient-group-badge">
                        @php $groupLabel = $allGroups->firstWhere('code', $patient->patient_group); @endphp
                        <span class="label label-info">{{ $groupLabel ? $groupLabel->name : $patient->patient_group }}</span>
                    </div>
                @endif
            </div>

            {{-- Tags --}}
            <div class="panel-section">
                <div class="panel-section-title">{{ __('patient.tags') }}</div>
                @php $patientTagIds = $patient->patientTags->pluck('id')->toArray(); @endphp
                @foreach($allTags as $tag)
                    <label class="tag-checkbox">
                        <input type="checkbox" name="panel_tags[]" value="{{ $tag->id }}"
                            {{ in_array($tag->id, $patientTagIds) ? 'checked' : '' }}>
                        {{ $tag->name }}
                    </label>
                @endforeach
            </div>

            {{-- Groups --}}
            <div class="panel-section">
                <div class="panel-section-title">{{ __('patient.patient_group') }}</div>
                <label class="group-radio">
                    <input type="radio" name="panel_group" value="" {{ !$patient->patient_group ? 'checked' : '' }}>
                    {{ __('common.none') }}
                </label>
                @foreach($allGroups as $g)
                    <label class="group-radio">
                        <input type="radio" name="panel_group" value="{{ $g->code }}" {{ $patient->patient_group === $g->code ? 'checked' : '' }}>
                        {{ $g->name }}
                    </label>
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="panel-actions">
                @if(!$patient->member_status || $patient->member_status === 'Inactive')
                    <a href="{{ url('members?patient_id=' . $patient->id) }}" class="btn btn-success btn-xs" title="{{ __('members.quick_register') }}">
                        <i class="fa fa-id-card"></i> {{ __('members.quick_register') }}
                    </a>
                @else
                    <a href="{{ url('members/' . $patient->id) }}" class="btn btn-info btn-xs" title="{{ __('members.member_details') }}">
                        <i class="fa fa-id-card"></i>
                    </a>
                @endif
                <button type="button" class="btn btn-default btn-xs" onclick="window.location.href='{{ url('patients') }}'">
                    <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Right Content (col-md-10) --}}
    <div class="col-md-10 patient-right-content">
        <div class="portlet light bordered">
            <div class="portlet-body">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#basic_info_tab" data-toggle="tab">{{ __('patient.basic_info') }}</a>
                        </li>
                        <li>
                            <a href="#dental_chart_tab" data-toggle="tab">{{ __('patient.dental_chart') }}</a>
                        </li>
                        <li>
                            <a href="#appointments_tab" data-toggle="tab">{{ __('patient.appointments') }} <span class="badge">{{ $appointmentsCount }}</span></a>
                        </li>
                        <li>
                            <a href="#medical_cases_tab" data-toggle="tab">{{ __('patient.medical_cases') }} <span class="badge">{{ $medicalCasesCount }}</span></a>
                        </li>
                        <li>
                            <a href="#images_tab" data-toggle="tab">{{ __('patient.images') }} <span class="badge">{{ $imagesCount }}</span></a>
                        </li>
                        <li>
                            <a href="#invoices_tab" data-toggle="tab">{{ __('patient.invoices') }} <span class="badge">{{ $invoicesCount }}</span></a>
                        </li>
                        <li>
                            <a href="#followups_tab" data-toggle="tab">{{ __('patient.followups') }} <span class="badge">{{ $followupsCount }}</span></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane active" id="basic_info_tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>{{ __('patient.personal_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.name') }}</th>
                                            <td><span class="pii-field" data-field="full_name_detail">{{ DataMaskingService::maskName($patient->full_name) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.gender') }}</th>
                                            <td>{{ $patient->gender == 'Male' ? __('patient.male') : __('patient.female') }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.dob') }}</th>
                                            <td>{{ $patient->dob ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.nin') }}</th>
                                            <td><span class="pii-field" data-field="nin">{{ DataMaskingService::maskNin($patient->nin) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.ethnicity') }}</th>
                                            <td>{{ $patient->ethnicity ? __('patient.ethnicity_' . $patient->ethnicity) : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.marital_status') }}</th>
                                            <td>{{ $patient->marital_status ? __('patient.marital_' . $patient->marital_status) : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.education') }}</th>
                                            <td>{{ $patient->education ? __('patient.education_' . $patient->education) : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.blood_type') }}</th>
                                            <td>
                                                @if($patient->blood_type)
                                                    @php
                                                        $bloodTypeKey = 'patient.blood_type_' . strtolower(str_replace('_', '_', $patient->blood_type));
                                                    @endphp
                                                    {{ __($bloodTypeKey) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.profession') }}</th>
                                            <td>{{ $patient->profession ?? '-' }}</td>
                                        </tr>
                                    </table>

                                    <h4>{{ __('patient.health_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.drug_allergy') }}</th>
                                            <td>
                                                @if($patient->drug_allergies)
                                                    @foreach(json_decode($patient->drug_allergies, true) ?? [] as $allergy)
                                                        <span class="label label-danger" style="margin-right:4px;">{{ __('patient.allergy_' . $allergy) }}</span>
                                                    @endforeach
                                                    @if($patient->drug_allergies_other)
                                                        <span class="label label-danger">{{ $patient->drug_allergies_other }}</span>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.chronic_diseases') }}</th>
                                            <td>
                                                @if($patient->systemic_diseases)
                                                    @foreach(json_decode($patient->systemic_diseases, true) ?? [] as $disease)
                                                        <span class="label label-warning" style="margin-right:4px;">{{ __('patient.disease_' . $disease) }}</span>
                                                    @endforeach
                                                    @if($patient->systemic_diseases_other)
                                                        <span class="label label-warning">{{ $patient->systemic_diseases_other }}</span>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.current_medication') }}</th>
                                            <td>{{ $patient->current_medication ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.special_conditions') }}</th>
                                            <td>
                                                @if($patient->is_pregnant)
                                                    <span class="label label-info">{{ __('patient.pregnant') }}</span>
                                                @endif
                                                @if($patient->is_breastfeeding)
                                                    <span class="label label-info">{{ __('patient.breastfeeding') }}</span>
                                                @endif
                                                @if(!$patient->is_pregnant && !$patient->is_breastfeeding)
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h4>{{ __('patient.contact_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.phone') }}</th>
                                            <td><span class="pii-field" data-field="phone_no">{{ DataMaskingService::maskPhone($patient->phone_no) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.alternative_phone') }}</th>
                                            <td><span class="pii-field" data-field="alternative_no">{{ DataMaskingService::maskPhone($patient->alternative_no) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.email') }}</th>
                                            <td><span class="pii-field" data-field="email">{{ DataMaskingService::maskEmail($patient->email) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.address') }}</th>
                                            <td><span class="pii-field" data-field="address">{{ DataMaskingService::maskAddress($patient->address) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.next_of_kin') }}</th>
                                            <td><span class="pii-field" data-field="next_of_kin">{{ DataMaskingService::maskName($patient->next_of_kin) ?? '-' }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.next_of_kin_phone') }}</th>
                                            <td><span class="pii-field" data-field="next_of_kin_no">{{ DataMaskingService::maskPhone($patient->next_of_kin_no) ?? '-' }}</span></td>
                                        </tr>
                                    </table>

                                    <h4>{{ __('patient.insurance_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.has_insurance') }}</th>
                                            <td>{{ $patient->has_insurance ? __('common.yes') : __('common.no') }}</td>
                                        </tr>
                                        @if($patient->has_insurance && $patient->InsuranceCompany)
                                        <tr>
                                            <th>{{ __('patient.insurance_company') }}</th>
                                            <td>{{ $patient->InsuranceCompany->name }}</td>
                                        </tr>
                                        @endif
                                    </table>

                                    @if($patient->source || $patient->referrer)
                                    <h4>{{ __('patient.source') }} / {{ __('patient.referred_by') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.source') }}</th>
                                            <td>{{ $patient->source ? $patient->source->name : '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.referred_by') }}</th>
                                            <td>
                                                @if($patient->referrer)
                                                    <a href="{{ url('patients/' . $patient->referrer->id) }}">{{ $patient->referrer->full_name }}</a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                    @endif

                                    @if($patient->sharedHolders && $patient->sharedHolders->count())
                                    <h4>{{ __('patient.kin_relations') }}</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('patient.name') }}</th>
                                                <th>{{ __('common.type') }}</th>
                                                <th>{{ __('patient.phone') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->sharedHolders as $kin)
                                                @if($kin->sharedPatient)
                                                <tr>
                                                    <td>
                                                        <a href="{{ url('patients/' . $kin->sharedPatient->id) }}">
                                                            {{ $kin->sharedPatient->full_name }}
                                                        </a>
                                                    </td>
                                                    <td>{{ __('members.relationship_' . ($kin->relationship ?? 'other')) }}</td>
                                                    <td>{{ $kin->sharedPatient->phone_no ?? '-' }}</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Dental Chart Tab -->
                        <div class="tab-pane" id="dental_chart_tab">
                            <div class="text-center" style="padding: 20px;">
                                <p>{{ __('patient.dental_chart_description') }}</p>
                                <button type="button" class="btn btn-primary" onclick="window.location.href='{{ url('medical-history/' . $patient->id) }}'">
                                    {{ __('patient.view_dental_history') }}
                                </button>
                            </div>
                        </div>

                        <!-- Appointments Tab -->
                        <div class="tab-pane" id="appointments_tab">
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_appointments_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('appointment.appointment_no') }}</th>
                                    <th>{{ __('appointment.date') }}</th>
                                    <th>{{ __('appointment.doctor') }}</th>
                                    <th>{{ __('appointment.status') }}</th>
                                    <th>{{ __('common.action') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Medical Cases Tab -->
                        <div class="tab-pane" id="medical_cases_tab">
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_cases_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('medical_cases.case_no') }}</th>
                                    <th>{{ __('medical_cases.title') }}</th>
                                    <th>{{ __('medical_cases.case_date') }}</th>
                                    <th>{{ __('medical_cases.status') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Images Tab -->
                        <div class="tab-pane" id="images_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addPatientImage()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_images_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('patient_images.title') }}</th>
                                    <th>{{ __('patient_images.image_type') }}</th>
                                    <th>{{ __('patient_images.image_date') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Invoices Tab -->
                        <div class="tab-pane" id="invoices_tab">
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_invoices_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('invoices.invoice_no') }}</th>
                                    <th>{{ __('invoices.date') }}</th>
                                    <th>{{ __('invoices.amount') }}</th>
                                    <th>{{ __('invoices.status') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- Follow-ups Tab -->
                        <div class="tab-pane" id="followups_tab">
                            <div class="table-toolbar">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn blue btn-outline sbold" onclick="addPatientFollowup()">
                                                {{ __('common.add_new') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <table class="table table-striped table-bordered table-hover table-checkable order-column" id="patient_followups_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('patient_followups.scheduled_date') }}</th>
                                    <th>{{ __('patient_followups.type') }}</th>
                                    <th>{{ __('patient_followups.purpose') }}</th>
                                    <th>{{ __('patient_followups.status') }}</th>
                                    <th>{{ __('common.view') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

@include('patients.modals.add_image')
@include('patients.modals.view_image')
@include('patients.modals.add_followup')
@include('patients.modals.view_followup')

@endsection
@section('js')
    <script>
        var global_patient_id = {{ $patient->id }};

        // Load translations for JavaScript
        LanguageManager.loadAllFromPHP({
            'patient': @json(__('patient')),
            'patient_images': @json(__('patient_images')),
            'patient_followups': @json(__('patient_followups')),
            'medical_cases': @json(__('medical_cases')),
            'appointment': @json(__('appointment')),
            'invoices': @json(__('invoices')),
            'messages': @json(__('messages')),
            'data_security': @json(__('data_security'))
        });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/patient_detail.js') }}"></script>
    <script>
        // Reveal / Hide PII toggle
        var piiRevealed = false;
        var piiMaskedValues = {};
        // Store initial masked values
        $('.pii-field').each(function() {
            var field = $(this).data('field');
            piiMaskedValues[field] = $(this).text();
        });

        $('#revealPiiBtn').click(function() {
            var btn = $(this);

            if (piiRevealed) {
                // Hide: restore masked values
                $('.pii-field').each(function() {
                    var field = $(this).data('field');
                    if (piiMaskedValues[field] !== undefined) {
                        $(this).text(piiMaskedValues[field]);
                    }
                });
                piiRevealed = false;
                btn.html('<i class="fa fa-eye"></i> ' + LanguageManager.trans('data_security.reveal_sensitive'));
                return;
            }

            // Reveal: fetch real data
            btn.prop('disabled', true);
            $.post('/patients/{{ $patient->id }}/reveal-pii', {_token: '{{ csrf_token() }}'}, function(resp) {
                $('.pii-field').each(function() {
                    var field = $(this).data('field');
                    if (resp[field] !== undefined && resp[field] !== null) {
                        $(this).text(resp[field]);
                    }
                });
                piiRevealed = true;
                btn.prop('disabled', false);
                btn.html('<i class="fa fa-eye-slash"></i> ' + LanguageManager.trans('data_security.hide_sensitive'));
            }).fail(function() {
                btn.prop('disabled', false);
                alert(LanguageManager.trans('data_security.reveal_failed'));
            });
        });

        // Left panel: auto-save tags and group changes
        $(document).on('change', 'input[name="panel_tags[]"], input[name="panel_group"]', function() {
            var tagIds = [];
            $('input[name="panel_tags[]"]:checked').each(function() {
                tagIds.push($(this).val());
            });
            var group = $('input[name="panel_group"]:checked').val();

            $.post('/patients/{{ $patient->id }}/quick-info', {
                _token: '{{ csrf_token() }}',
                tag_ids: tagIds,
                patient_group: group
            }, function(resp) {
                if (resp.status) {
                    toastr.success(resp.message);
                }
            });
        });
    </script>
@endsection
