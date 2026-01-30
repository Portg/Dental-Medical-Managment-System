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
                    <span class="caption-subject">
                        <a href="{{ url('patients') }}" class="text-primary">{{ __('patient.patients') }}</a>
                        / {{ $patient->surname }} {{ $patient->othername }} ({{ $patient->patient_no }})
                    </span>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-default btn-sm" onclick="window.location.href='{{ url('patients') }}'">
                        {{ __('common.back') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                <!-- Patient Info Summary -->
                <div class="row">
                    <div class="col-md-2 text-center">
                        @if($patient->photo)
                            <img src="{{ asset('images/' . $patient->photo) }}" class="img-circle" style="width:100px; height:100px; object-fit:cover;">
                        @else
                            <img src="{{ asset('images/default-avatar.png') }}" class="img-circle" style="width:100px; height:100px;">
                        @endif
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.patient_no') }}:</strong><br>{{ $patient->patient_no }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.name') }}:</strong><br>{{ $patient->surname }} {{ $patient->othername }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.gender') }}:</strong><br>{{ $patient->gender == 'Male' ? __('patient.male') : __('patient.female') }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.dob') }}:</strong><br>{{ $patient->dob ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.phone') }}:</strong><br>{{ $patient->phone_no ?? '-' }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.email') }}:</strong><br>{{ $patient->email ?? '-' }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.insurance') }}:</strong><br>
                                    @if($patient->has_insurance == 'Yes' && $patient->InsuranceCompany)
                                        {{ $patient->InsuranceCompany->name }}
                                    @else
                                        {{ __('common.no') }}
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>{{ __('patient.address') }}:</strong><br>{{ $patient->address ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="global_patient_id" value="{{ $patient->id }}">

<div class="row">
    <div class="col-md-12">
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
                                            <th width="40%">{{ __('patient.surname') }}</th>
                                            <td>{{ $patient->surname }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.othername') }}</th>
                                            <td>{{ $patient->othername }}</td>
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
                                            <td>{{ $patient->nin ?? '-' }}</td>
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
                                </div>
                                <div class="col-md-6">
                                    <h4>{{ __('patient.contact_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.phone') }}</th>
                                            <td>{{ $patient->phone_no ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.alternative_phone') }}</th>
                                            <td>{{ $patient->alternative_no ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.email') }}</th>
                                            <td>{{ $patient->email ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.address') }}</th>
                                            <td>{{ $patient->address ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.next_of_kin') }}</th>
                                            <td>{{ $patient->next_of_kin ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('patient.next_of_kin_phone') }}</th>
                                            <td>{{ $patient->next_of_kin_no ?? '-' }}</td>
                                        </tr>
                                    </table>

                                    <h4>{{ __('patient.insurance_info') }}</h4>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="40%">{{ __('patient.has_insurance') }}</th>
                                            <td>{{ $patient->has_insurance == 'Yes' ? __('common.yes') : __('common.no') }}</td>
                                        </tr>
                                        @if($patient->has_insurance == 'Yes' && $patient->InsuranceCompany)
                                        <tr>
                                            <th>{{ __('patient.insurance_company') }}</th>
                                            <td>{{ $patient->InsuranceCompany->name }}</td>
                                        </tr>
                                        @endif
                                    </table>
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
            'messages': @json(__('messages'))
        });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/patient_detail.js') }}"></script>
@endsection
