{{-- Patient Info Card --}}
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.patient_info') }}</span>
        </div>
    </div>
    <div class="portlet-body">
        @if($needPatientSelection)
            {{-- Patient Selector (for create mode without patient) --}}
            <div id="patient-selector-section">
                <select name="patient_selector" id="patient_selector" class="form-control select2" style="width: 100%;">
                    <option value=""></option>
                </select>
            </div>

            {{-- Selected Patient Info (hidden initially) --}}
            <div id="selected-patient-info" style="display: none;">
                <div class="row">
                    <div class="col-xs-3">
                        <div class="patient-avatar-large" id="patient-avatar">-</div>
                    </div>
                    <div class="col-xs-7">
                        <div class="name" id="patient-name" style="font-weight: 600;">-</div>
                        <div class="meta text-muted" id="patient-meta">-</div>
                    </div>
                    <div class="col-xs-2">
                        <button type="button" class="btn btn-xs btn-default" onclick="changePatient()">
                            {{ __('common.change') }}
                        </button>
                    </div>
                </div>
                <div class="alert alert-warning" id="patient-allergy-warning" style="display: none; margin-top: 10px; padding: 8px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span></span>
                </div>
                <div class="text-muted" id="patient-chronic-info" style="display: none; margin-top: 8px; font-size: 12px;">
                    <strong>{{ __('medical_cases.chronic_diseases') }}:</strong>
                    <span></span>
                </div>
            </div>
        @else
            {{-- Display existing patient info --}}
            <div class="row">
                <div class="col-xs-3">
                    <div class="patient-avatar-large">
                        {{ $currentPatient ? substr($currentPatient->surname, 0, 1) : '-' }}
                    </div>
                </div>
                <div class="col-xs-9">
                    <div style="font-weight: 600;">
                        {{ $currentPatient ? $currentPatient->full_name : '-' }}
                    </div>
                    <div class="text-muted" style="font-size: 12px;">
                        @if($currentPatient)
                            {{ $currentPatient->gender == 'Male' ? __('patient.male') : __('patient.female') }}
                            @if($currentPatient->dob)
                                {{ \Carbon\Carbon::parse($currentPatient->dob)->age }}{{ __('common.years_old') }}
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @if($currentPatient && $currentPatient->drug_allergies_other)
                <div class="alert alert-warning" style="margin-top: 10px; padding: 8px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span>{{ __('medical_cases.patient_allergy') }}: {{ $currentPatient->drug_allergies_other }}</span>
                </div>
            @endif

            @if($currentPatient && $currentPatient->chronic_diseases)
                <div class="text-muted" style="margin-top: 8px; font-size: 12px;">
                    <strong>{{ __('medical_cases.chronic_diseases') }}:</strong>
                    {{ $currentPatient->chronic_diseases }}
                </div>
            @endif
        @endif
    </div>
</div>
