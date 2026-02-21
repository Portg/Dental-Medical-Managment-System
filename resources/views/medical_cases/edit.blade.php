@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('medical_cases.medical_record_edit'))

@php
    $currentPatient = isset($case) ? $case->patient : ($patient ?? null);
    $isCreateMode = !isset($case);
    $needPatientSelection = $isCreateMode && !$currentPatient;
@endphp

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/medical-record-edit.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tooth-selector.css') }}">
@endsection

@section('content')
<div class="row">
    {{-- Main Form Panel (Left) --}}
    <div class="col-md-8">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">
                        <a href="{{ url('medical-cases') }}" class="text-primary">{{ __('medical_cases.page_title') }}</a>
                        / {{ $isCreateMode ? __('medical_cases.add_case') : __('medical_cases.edit_case') }}
                        @if(isset($case) && $case->is_draft)
                            <span class="label label-warning">{{ __('medical_cases.draft_status') }}</span>
                        @endif
                    </span>
                </div>
                <div class="actions">
                    <button type="button" class="btn btn-default" id="btn-save-draft"
                            onclick="saveMedicalRecord('draft')" @if($needPatientSelection) disabled @endif>
                        {{ __('medical_cases.save_draft') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-submit-record"
                            onclick="saveMedicalRecord('submit')" @if($needPatientSelection) disabled @endif>
                        {{ __('medical_cases.submit_record') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                {{-- Patient Selection Prompt --}}
                @if($needPatientSelection)
                <div class="alert alert-info" id="patient-select-prompt">
                    <i class="fa fa-info-circle"></i>
                    {{ __('medical_cases.select_patient_hint') }}
                </div>
                @endif

                <div class="alert alert-danger" id="form-errors" style="display:none">
                    <ul></ul>
                </div>

                <form id="medical-record-form" autocomplete="off">
                <div id="record-form-body" class="@if($needPatientSelection) disabled @endif">
                    @csrf
                    <input type="hidden" name="id" id="case_id" value="{{ $case->id ?? '' }}">
                    <input type="hidden" name="patient_id" id="patient_id" value="{{ $case->patient_id ?? $patient->id ?? '' }}">

                    {{-- Visit Information --}}
                    @include('medical_cases.partials.visit_info', ['case' => $case ?? null, 'doctors' => $doctors])

                    {{-- Chief Complaint (S) --}}
                    @include('medical_cases.partials.soap_section', [
                        'id' => 'chief_complaint',
                        'title' => __('medical_cases.chief_complaint_section'),
                        'hint' => __('medical_cases.chief_complaint_hint'),
                        'placeholder' => __('medical_cases.subjective_placeholder'),
                        'value' => $case->chief_complaint ?? '',
                        'required' => true,
                        'maxlength' => 500,
                        'showCounter' => true,
                        'showTemplates' => true
                    ])

                    {{-- History of Present Illness --}}
                    @include('medical_cases.partials.soap_section', [
                        'id' => 'history_of_present_illness',
                        'title' => __('medical_cases.present_illness_section'),
                        'hint' => __('medical_cases.present_illness_hint'),
                        'value' => $case->history_of_present_illness ?? '',
                        'required' => false
                    ])

                    {{-- Examination (O) --}}
                    @include('medical_cases.partials.examination_section', ['case' => $case ?? null])

                    {{-- Auxiliary Examination --}}
                    @include('medical_cases.partials.auxiliary_section', ['case' => $case ?? null])

                    {{-- Diagnosis (A) --}}
                    @include('medical_cases.partials.diagnosis_section', ['case' => $case ?? null])

                    {{-- Treatment (P) --}}
                    @include('medical_cases.partials.treatment_section', ['case' => $case ?? null])

                    {{-- Medical Orders --}}
                    @include('medical_cases.partials.soap_section', [
                        'id' => 'medical_orders',
                        'title' => __('medical_cases.medical_orders_section'),
                        'hint' => __('medical_cases.medical_orders_hint'),
                        'value' => $case->medical_orders ?? '',
                        'required' => false
                    ])

                    {{-- Follow-up Section --}}
                    @include('medical_cases.partials.followup_section', ['case' => $case ?? null])

                    {{-- Quality Control Panel --}}
                    <div class="qc-panel" id="qc-panel">
                        <div class="qc-panel-title">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ __('medical_cases.quality_check') }}
                        </div>
                        <div id="qc-items"></div>
                    </div>
                </div>{{-- End record-form-body --}}
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar (Right) --}}
    <div class="col-md-4">
        <div class="sidebar-sticky-wrapper">
            {{-- Patient Info Card --}}
            @include('medical_cases.partials.sidebar_patient', [
                'needPatientSelection' => $needPatientSelection,
                'currentPatient' => $currentPatient
            ])

            {{-- Tooth Chart Mini --}}
            @include('medical_cases.partials.sidebar_tooth_chart')

            {{-- History Records --}}
            @include('medical_cases.partials.sidebar_history', ['historyRecords' => $historyRecords ?? []])

            {{-- Quick Phrases --}}
            @include('medical_cases.partials.sidebar_quick_phrases')
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

{{-- Tooth Selector Modal --}}
@include('medical_cases.partials.tooth_selector_modal')

{{-- Service Selector Modal --}}
@include('medical_cases.partials.service_selector_modal')

{{-- Image Upload Modal --}}
@include('medical_cases.partials.image_upload_modal')

{{-- Signature Pad Modal --}}
<div class="modal fade" id="signatureModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">{{ __('medical_cases.doctor_signature') }}</h4>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted">{{ __('medical_cases.signature_hint') }}</p>
                <canvas id="signature-canvas" width="460" height="200" style="border:1px solid #ddd; border-radius:4px; cursor:crosshair;"></canvas>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btn-clear-signature">{{ __('common.clear') }}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-signature">{{ __('common.confirm') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
{{-- Configuration for JavaScript --}}
<script>
var needPatientSelection = {{ $needPatientSelection ? 'true' : 'false' }};
var MedicalRecordConfig = {
    urls: {
        searchPatient: '{{ url("search-patient") }}',
        medicalCases: '{{ url("medical-cases") }}'
    },
    translations: {
        // Patient selection
        searchAndSelectPatient: '{{ __("medical_cases.search_and_select_patient") }}',
        typeToSearch: '{{ __("common.type_to_search") }}',
        noResults: '{{ __("common.no_results") }}',
        searching: '{{ __("common.searching") }}',
        selectDoctor: '{{ __("medical_cases.select_doctor") }}',

        // Patient info
        male: '{{ __("patient.male") }}',
        female: '{{ __("patient.female") }}',
        yearsOld: '{{ __("common.years_old") }}',
        patientAllergy: '{{ __("medical_cases.patient_allergy") }}',

        // Actions
        expand: '{{ __("medical_cases.expand") }}',
        collapse: '{{ __("medical_cases.collapse") }}',
        comingSoon: '{{ __("common.coming_soon") }}',

        // Validation
        chiefComplaintRequired: '{{ __("medical_cases.chief_complaint_required") }}',
        examinationRequired: '{{ __("medical_cases.examination_required") }}',
        diagnosisRequired: '{{ __("medical_cases.diagnosis_required") }}',
        treatmentRequired: '{{ __("medical_cases.treatment_required") }}',

        // Quality control
        qcChiefComplaint: '{{ __("medical_cases.qc_chief_complaint") }}',
        qcChiefComplaintRule: '{{ __("medical_cases.qc_chief_complaint_rule") }}',
        qcDiagnosisStandard: '{{ __("medical_cases.qc_diagnosis_standard") }}',
        qcDiagnosisRule: '{{ __("medical_cases.qc_diagnosis_rule") }}',
        qcTeethClarity: '{{ __("medical_cases.qc_teeth_clarity") }}',
        qcTeethRule: '{{ __("medical_cases.qc_teeth_rule") }}',
        qcTreatmentLink: '{{ __("medical_cases.qc_treatment_link") }}',
        qcTreatmentRule: '{{ __("medical_cases.qc_treatment_rule") }}',

        // Messages
        draftSaved: '{{ __("medical_cases.draft_saved") }}',
        recordSubmitted: '{{ __("medical_cases.record_submitted") }}',
        errorOccurred: '{{ __("messages.error_occurred") }}',
        amendmentSubmitted: '{{ __("medical_cases.amendment_submitted") }}',

        // Signature
        signatureRequired: '{{ __("medical_cases.signature_required") }}',
        signatureSaved: '{{ __("medical_cases.signature_saved") }}',
        editRequiresApproval: '{{ __("medical_cases.edit_requires_approval") }}',
        modificationReason: '{{ __("medical_cases.modification_reason") }}'
    }
};

// Load templates translations for TemplatePicker
LanguageManager.loadAllFromPHP({
    'templates': @json(__('templates'))
});
</script>
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/template_picker.js') }}"></script>
<script src="{{ asset('include_js/signature_pad.umd.min.js') }}"></script>
<script src="{{ asset('include_js/medical_record_edit.js') }}"></script>
@endsection
