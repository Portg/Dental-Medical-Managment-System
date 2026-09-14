@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('medical_cases.medical_record_edit'))

@php
    $currentPatient = isset($case) ? $case->patient : ($patient ?? null);
    $isCreateMode = !isset($case);
    $needPatientSelection = $isCreateMode && !$currentPatient;

    // 纸面上的年龄：优先按出生日期算，没有出生日期才用存下来的 age。
    $paperAge = null;
    if ($currentPatient) {
        if ($currentPatient->date_of_birth) {
            $paperAge = \Carbon\Carbon::parse($currentPatient->date_of_birth)->age;
        } elseif ($currentPatient->age !== null && $currentPatient->age !== '') {
            $paperAge = (int) $currentPatient->age;
        }
    }
@endphp
@section('page_title', $isCreateMode ? __('medical_cases.add_case') : __('medical_cases.edit_case'))

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/medical-record-edit.css') }}?v={{ filemtime(public_path('css/medical-record-edit.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/tooth-selector.css') }}?v={{ filemtime(public_path('css/tooth-selector.css')) }}">
    {{-- 病历纸的版式。必须排在 medical-record-edit.css 之后 —— 卡片样式由它覆盖。 --}}
    <link rel="stylesheet" href="{{ asset('css/medical-record-paper.css') }}?v={{ filemtime(public_path('css/medical-record-paper.css')) }}">
@endsection

@section('content')
{{-- 工具条。不在纸上，也不打印 —— 它是操作，不是病历内容。 --}}
<div class="mr-toolbar">
    <div class="mr-toolbar-title">
        <a href="{{ url('medical-cases') }}" class="text-primary">{{ __('medical_cases.page_title') }}</a>
        / {{ $isCreateMode ? __('medical_cases.add_case') : __('medical_cases.edit_case') }}
        @if(isset($case) && $case->is_draft)
            <span class="label label-warning">{{ __('medical_cases.draft_status') }}</span>
        @endif
    </div>
    <div class="mr-toolbar-actions">
        <button type="button" class="btn btn-default" id="btn-save-draft"
                onclick="saveMedicalRecord('draft')" @if($needPatientSelection) disabled @endif>
            {{ __('medical_cases.save_draft') }}
        </button>
        <button type="button" class="btn btn-primary" id="btn-submit-record"
                onclick="saveMedicalRecord('submit')" @if($needPatientSelection) disabled @endif>
            {{ __('medical_cases.submit_record') }}
        </button>
        {{-- 打印就是打这张纸，不跳转、不另生成一份版式。原来编辑页压根没有打印
             入口：要先保存、再跳到详情页才找得到，而打出来的还是另一套版式。 --}}
        <button type="button" class="btn btn-default" id="btn-print-record"
                onclick="printMedicalRecord()" @if($needPatientSelection) disabled @endif>
            <i class="fa fa-print"></i> {{ __('common.print') }}
        </button>
    </div>
</div>

<div class="mr-stage">
    <div class="mr-layout">
        <div class="mr-paper-col">
            <div class="mr-paper">
                {{-- 抬头 --}}
                <div class="mr-sheet-head">
                    <div class="mr-clinic">{{ __('company.name') }}</div>
                    <div class="mr-sheet-title">{{ __('medical_cases.medical_record') }}</div>
                    <div class="mr-sheet-meta">
                        {{ __('medical_cases.case_no') }}:
                        {{ $case->case_no ?? __('medical_cases.case_no_pending') }}
                    </div>
                </div>
                <div class="mr-rule-double"></div>

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
                    {{-- 这份病历写的是哪一次就诊。工作台点「开病历」会带着 appointment_id 过来，
                         保存时回填 appointments.medical_case_id —— 一次就诊一份病历，
                         没有这个字段，病历和就诊各记各的，谁也不知道今天这次看诊写没写病历。
                         fillFromAppointment（补充病历）也写这个框，此前它写的是一个
                         页面上根本不存在的 #appointment_id，选了等于没选。 --}}
                    <input type="hidden" name="appointment_id" id="appointment_id"
                           value="{{ $appointmentId ?? '' }}">

                    {{-- 患者身份行。这些 id 是 enableFormWithPatient() 写的目标：
                         建档模式下先是空的，在侧栏选完患者由 JS 填进来。
                         刻意只在这里出现一次 —— 侧栏那张卡片不再重复显示姓名，
                         否则就是两个同 id 的元素（这套代码栽过一次，见 46287dc）。 --}}
                    <div class="mr-identity">
                        <div class="mr-cell">
                            <span class="mr-cell-key">{{ __('common.patient') }}</span>
                            <span class="mr-cell-val" id="patient-name">{{ $currentPatient->full_name ?? '' }}</span>
                        </div>
                        <div class="mr-cell">
                            <span class="mr-cell-key">{{ __('common.gender') }}/{{ __('common.age') }}</span>
                            <span class="mr-cell-val" id="patient-meta">@if($currentPatient){{ $currentPatient->gender == 'Male' ? __('patient.male') : __('patient.female') }}@if($paperAge !== null) {{ $paperAge }}{{ __('common.years_old') }}@endif @endif</span>
                        </div>
                        <div class="mr-cell">
                            <span class="mr-cell-key">{{ __('common.phone') }}</span>
                            <span class="mr-cell-val">{{ $currentPatient->phone_no ?? '' }}</span>
                        </div>
                    </div>

                    {{-- 过敏史印在病历上，不只是界面提醒 —— 这是接诊时必须看见的医疗信息。 --}}
                    <div class="mr-allergy" id="patient-allergy-warning"
                         @if(!($currentPatient && $currentPatient->drug_allergies_other)) style="display:none" @endif>
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>@if($currentPatient && $currentPatient->drug_allergies_other){{ __('medical_cases.patient_allergy') }}：{{ $currentPatient->drug_allergies_other }}@endif</span>
                    </div>
                    <div class="mr-allergy" id="patient-chronic-info" style="display:none">
                        <strong>{{ __('medical_cases.chronic_diseases') }}:</strong>
                        <span></span>
                    </div>

                    {{-- 就诊信息（日期 / 接诊医生 / 初诊复诊 / 第几次） --}}
                    @include('medical_cases.partials.visit_info', ['case' => $case ?? null, 'doctors' => $doctors])

                    <div class="mr-rule"></div>

                    {{-- 主诉 / 现病史 / 既往史 合成一个分组，标签在左。

                         原来三段各占一张独立卡片、标签在上、文本框各约 100px 高，
                         光这三段就吃掉近 400px —— 而它们是同一件事的三个侧面
                         （患者怎么说的），本来就该放在一起看。
                         参考视频：这三行是一个可折叠的「主诉/现病史/既往史」分组。 --}}
                    <div class="soap-section narrative-group">
                        <div class="soap-section-header">
                            <div class="soap-section-title">
                                {{ __('medical_cases.narrative_group') }}
                                <span class="required">*</span>
                            </div>
                            <button type="button" class="btn btn-xs btn-link js-toggle-narrative">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="soap-section-body narrative-rows">
                            <div class="narrative-row">
                                <label for="chief_complaint">{{ __('medical_cases.chief_complaint_section') }} <span class="required">*</span></label>
                                <div class="narrative-field">
                                    <textarea name="chief_complaint" id="chief_complaint" class="soap-textarea"
                                              rows="2" maxlength="500" required
                                              placeholder="{{ __('medical_cases.subjective_placeholder') }}">{{ $case->chief_complaint ?? '' }}</textarea>
                                    <div class="char-counter">
                                        <span id="chief_complaint_count">{{ mb_strlen($case->chief_complaint ?? '') }}</span>/500
                                    </div>
                                    <div class="template-triggers js-template-quick-buttons"
                                         data-field="chief_complaint" data-template-type="chief_complaint"></div>
                                </div>
                            </div>

                            <div class="narrative-row">
                                <label for="history_of_present_illness">{{ __('medical_cases.present_illness_section') }}</label>
                                <div class="narrative-field">
                                    <textarea name="history_of_present_illness" id="history_of_present_illness"
                                              class="soap-textarea" rows="2"
                                              placeholder="{{ __('medical_cases.present_illness_hint') }}">{{ $case->history_of_present_illness ?? '' }}</textarea>
                                </div>
                            </div>

                            <div class="narrative-row">
                                <label for="past_medical_history">{{ __('medical_cases.past_history_section') }}</label>
                                <div class="narrative-field">
                                    <textarea name="past_medical_history" id="past_medical_history"
                                              class="soap-textarea" rows="2"
                                              placeholder="{{ __('medical_cases.past_history_placeholder') }}">{{ $case->past_medical_history ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <div class="mr-rule"></div>

                    {{-- Follow-up Section --}}
                    @include('medical_cases.partials.followup_section', ['case' => $case ?? null])

                    {{-- 落款。签名是提交时在签名板上写的（$case->signature），
                         已经签过的就把签名图印在线上，没签过留空线 —— 和纸质病历一样。 --}}
                    <div class="mr-sign">
                        <div class="mr-sign-item">
                            <div class="mr-sign-slot">
                                @if(isset($case) && $case->signature && str_starts_with($case->signature, 'data:image'))
                                    <img src="{{ $case->signature }}" alt="{{ __('medical_cases.doctor_signature') }}">
                                @endif
                            </div>
                            <div class="mr-sign-line">{{ __('medical_cases.doctor_signature') }}</div>
                        </div>
                        <div class="mr-sign-item">
                            <div class="mr-sign-slot"></div>
                            <div class="mr-sign-line">{{ __('medical_cases.case_date') }}</div>
                        </div>
                    </div>

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

        {{-- 工具侧栏。写病历时要用（牙位图、上次病历、快捷短语），但它不是病历
             本身 —— 不进纸面，也不打印。 --}}
        <aside class="mr-tools">
            <div class="sidebar-sticky-wrapper">
                @include('medical_cases.partials.sidebar_patient', [
                    'needPatientSelection' => $needPatientSelection,
                    'currentPatient' => $currentPatient
                ])

                @include('medical_cases.partials.sidebar_tooth_chart')

                @include('medical_cases.partials.sidebar_history', ['historyRecords' => $historyRecords ?? []])

                @include('medical_cases.partials.sidebar_quick_phrases')
            </div>
        </aside>
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
                <canvas id="signature-canvas" width="460" height="200" style="width:460px; max-width:100%; height:200px; box-sizing:border-box; border:1px solid #ddd; border-radius:4px; cursor:crosshair;"></canvas>
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
        confirmCopyOverwrite: @json(__('medical_cases.confirm_copy_overwrite')),
        copiedFromPrevious:   @json(__('medical_cases.copied_from_previous')),
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
<script src="{{ asset('include_js/template_picker.js') }}?v={{ filemtime(public_path('include_js/template_picker.js')) }}"></script>
{{-- 分段明细（牙位 + 文字）的分行编辑；必须在 medical_record_edit.js 之前，
     后者的 $(document).ready 会调 CaseItems.init() --}}
<script src="{{ asset('include_js/medical_case_items.js') }}?v={{ filemtime(public_path('include_js/medical_case_items.js')) }}"></script>
<script src="{{ asset('include_js/signature_pad.umd.min.js') }}?v={{ filemtime(public_path('include_js/signature_pad.umd.min.js')) }}"></script>
<script src="{{ asset('include_js/signature_pad_compat.js') }}?v={{ filemtime(public_path('include_js/signature_pad_compat.js')) }}"></script>
<script src="{{ asset('include_js/medical_record_edit.js') }}?v={{ filemtime(public_path('include_js/medical_record_edit.js')) }}"></script>
{{-- 纸面行为：文本域跟着内容长高（不然打印只印出可见的那几行）、打印入口。
     排在最后 —— 它要量的是前面那些脚本渲染完的行。 --}}
<script src="{{ asset('include_js/medical_record_paper.js') }}?v={{ filemtime(public_path('include_js/medical_record_paper.js')) }}"></script>
@endsection
