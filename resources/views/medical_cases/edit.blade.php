@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('medical_cases.medical_record_edit'))

@php
    $currentPatient = isset($case) ? $case->patient : ($patient ?? null);
    $isCreateMode = !isset($case);
    $needPatientSelection = $isCreateMode && !$currentPatient;
@endphp
@section('page_title', $isCreateMode ? __('medical_cases.add_case') : __('medical_cases.edit_case'))

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/medical-record-edit.css') }}?v={{ filemtime(public_path('css/medical-record-edit.css')) }}">
    {{-- 锚定短语面板 --}}
    <link rel="stylesheet" href="{{ asset('css/phrase-panel.css') }}?v={{ filemtime(public_path('css/phrase-panel.css')) }}">
    {{-- 牙位网格 + 软键盘 --}}
    <link rel="stylesheet" href="{{ asset('css/tooth-grid.css') }}?v={{ filemtime(public_path('css/tooth-grid.css')) }}">
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
                    {{-- 打印：录入在这张表单里，打出来是病历纸（medical_cases.sheet）。
                         表单是录入态、纸是呈现态，两者不混。

                         原来这一页压根没有打印入口 —— 医生写完得先保存、再跳到病历
                         详情页才找得到。新建还没保存的病历没有 id，也就没有纸可打，
                         此时按钮禁用。 --}}
                    @isset($case)
                        <a class="btn btn-default" id="btn-print-record" target="_blank"
                           href="{{ url('print-medical-case/' . $case->id) }}">
                            <i class="fa fa-print"></i> {{ __('common.print') }}
                        </a>
                    @else
                        <button type="button" class="btn btn-default" id="btn-print-record"
                                disabled title="{{ __('medical_cases.print_after_save') }}">
                            <i class="fa fa-print"></i> {{ __('common.print') }}
                        </button>
                    @endisset
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
                    {{-- 这份病历写的是哪一次就诊。工作台点「开病历」会带着 appointment_id 过来，
                         保存时回填 appointments.medical_case_id —— 一次就诊一份病历，
                         没有这个字段，病历和就诊各记各的，谁也不知道今天这次看诊写没写病历。
                         fillFromAppointment（补充病历）也写这个框，此前它写的是一个
                         页面上根本不存在的 #appointment_id，选了等于没选。 --}}
                    <input type="hidden" name="appointment_id" id="appointment_id"
                           value="{{ $appointmentId ?? '' }}">

                    {{-- Visit Information --}}
                    @include('medical_cases.partials.visit_info', ['case' => $case ?? null, 'doctors' => $doctors])

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
                                              data-phrase-field="chief_complaint"
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
                                              class="soap-textarea" rows="2" data-phrase-field="present_illness"
                                              placeholder="{{ __('medical_cases.present_illness_hint') }}">{{ $case->history_of_present_illness ?? '' }}</textarea>
                                </div>
                            </div>

                            <div class="narrative-row">
                                <label for="past_medical_history">{{ __('medical_cases.past_history_section') }}</label>
                                <div class="narrative-field">
                                    <textarea name="past_medical_history" id="past_medical_history"
                                              class="soap-textarea" rows="2" data-phrase-field="past_history"
                                              placeholder="{{ __('medical_cases.past_history_placeholder') }}">{{ $case->past_medical_history ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 全局工具条。参考产品在所有分段之上就有这么一条。

                         「复制牙位」原来在每个段落里各放一份 —— 同一个动作重复四遍。
                         「全部展开 / 折叠」是原来没有的：复诊翻旧病历时，一屏里
                         四段十几行，一键折叠才看得过来。

                         没做「特殊符号」：参考用它插 ± ° Ⅰ Ⅱ Ⅲ、（+）（−），
                         而我们的短语库里已经有「松动Ⅰ度，」「探诊（+），」这类成品，
                         再来一个符号面板是重复。等短语库用一阵子再看缺不缺。 --}}
                    <div class="case-sections-toolbar">
                        <button type="button" class="btn btn-xs btn-link js-toggle-all-sections" data-collapse="false">
                            <i class="fa fa-plus-square-o"></i> {{ __('medical_cases.expand_all') }}
                        </button>
                        <button type="button" class="btn btn-xs btn-link js-toggle-all-sections" data-collapse="true">
                            <i class="fa fa-minus-square-o"></i> {{ __('medical_cases.collapse_all') }}
                        </button>
                    </div>

                    {{-- Examination (O) --}}
                    @include('medical_cases.partials.examination_section', ['case' => $case ?? null])

                    {{-- Auxiliary Examination --}}
                    @include('medical_cases.partials.auxiliary_section', ['case' => $case ?? null])

                    {{-- Diagnosis (A) --}}
                    @include('medical_cases.partials.diagnosis_section', ['case' => $case ?? null])

                    {{-- Treatment (P) --}}
                    {{-- 治疗计划（打算做什么）在前，治疗（这次做了什么）在后 ——
                         与参考产品的段落顺序一致，也符合医生的思考顺序：
                         先定方案，再记这次做到哪一步。 --}}
                    @include('medical_cases.partials.treatment_plan_section', ['case' => $case ?? null])

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

            {{-- 侧栏牙位图已去掉：它与牙位软键盘是同一件事的两份实现。
                 软键盘就出现在正在编辑的那一行上，「往哪写」不言自明；
                 侧栏那份离光标远，还要先让某一行获得焦点才知道往哪写。 --}}

            {{-- History Records --}}
            @include('medical_cases.partials.sidebar_history', ['historyRecords' => $historyRecords ?? []])

            {{-- 侧栏快捷短语已去掉。

                 短语面板改成锚定在正在编辑的字段上之后，这一份就成了同一批数据的
                 远距离副本；而短语库从 30 条扩到 439 条、九个字段之后，它在侧栏里
                 摊开就是一面墙 —— 越全越没法用。

                 <kbd>/</kbd> 模板、<kbd>;</kbd> 短语两个键盘入口挪到了锚定面板的
                 页脚上，就在医生眼前，比压在侧栏底部更容易被发现。 --}}
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

{{-- 牙位软键盘的模板。点行里的牙位格时，tooth_pad.js 把这段搬到 body 下、
     贴着那一行弹出来 —— 与短语面板同一个思路：要用的东西出现在光标旁边。

     原来这里是一个模态框：盖住正在写的那一行，选完还要点「确认」再关掉，
     一行里改两次牙位就得开关两次弹窗。 --}}
<div id="tooth-pad-template" style="display:none">
    @include('medical_cases.partials.tooth_grid', [
        'idPrefix' => 'tooth-pad-grid',
        'compact'  => false,
        'onclick'  => null,
    ])
    {{-- 牙位标记（部位记录法里写在牙位号上下的符号）。参考产品的选择器底部
         就是这三个。落在**行**上：一行是一条临床陈述，「16,17 残根」就是两颗
         都残根；再点同一个等于取消。 --}}
    <div class="tooth-pad-marks">
        @foreach(\App\MedicalCaseItem::MARKS as $mark)
            <button type="button" class="tooth-pad-mark" data-mark="{{ $mark }}"
                    data-symbol="{{ \App\MedicalCaseItem::MARK_SYMBOLS[$mark] }}"
                    title="{{ __('medical_cases.tooth_mark_' . $mark) }}">
                {{ \App\MedicalCaseItem::MARK_SYMBOLS[$mark] }}
                <span class="tooth-pad-mark-label">{{ __('medical_cases.tooth_mark_' . $mark) }}</span>
            </button>
        @endforeach
    </div>
    {{-- 拿起标记笔后才出现：把一个光秃秃的符号放进某个象限，不指名哪颗牙。

         做成一个迷你十字而不是四个写着「右上/左上…」的按钮 —— 它长得就是它
         产生的结果（行里那个十字），点哪格符号就落哪格，不需要一句话解释。
         象限名留在 title 里，给悬停和读屏用，不占视觉。

         格子顺序按 toothQuadrant 的镜像规则：左上格=患者右上=1，右上格=2，
         左下格=患者右下=4，右下格=3。存一位数字的象限码，见
         MedicalCaseItem::isQuadrantCode。 --}}
    <div class="tooth-pad-quadrants">
        @foreach(['ur' => 1, 'ul' => 2, 'lr' => 4, 'll' => 3] as $key => $code)
            <span class="tpq" data-quadrant-code="{{ $code }}"
                  title="{{ __('medical_cases.quadrant_' . $key) }}"></span>
        @endforeach
    </div>
    <div class="tooth-pad-foot">
        <span class="tooth-pad-hint">{{ __('medical_cases.tooth_pad_hint') }}</span>
        <button type="button" class="btn btn-xs btn-default tooth-pad-done">{{ __('common.close') }}</button>
    </div>
</div>

{{-- 服务选择器弹窗已去掉：它唯一的入口是治疗项目那块的「添加项目」，
     而治疗项目已从病历页移除（划价面板是唯一开单入口）。 --}}

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
    // 锚定短语面板的整份数据：[病历字段 => [语义槽位 => [短语, ...]]]。
    // 一次性注进来而不是每次聚焦发 ajax —— 光标在字段之间来回跳，
    // 每跳一次等一次网络是最不该有的等待。约 10KB。
    phrasePanel: @json($phrasePanel ?? []),
    // 段落名从服务端下发：这一页只往 LanguageManager 灌了 templates 一组，
    // medical_cases.* 在前端拿不到，面板标题会印出键名而不是「现病史」。
    phraseLabels: @json(collect(array_keys($phrasePanel ?? []))
        ->mapWithKeys(fn ($f) => [$f => __('medical_cases.phrase_category_' . $f)])
        ->merge([
            // 面板页脚的两个键盘入口（原来压在侧栏底部，侧栏已去掉）
            'medical_cases.hint_template_key' => __('medical_cases.hint_template_key'),
            'medical_cases.hint_phrase_key'   => __('medical_cases.hint_phrase_key'),
        ])),
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
{{-- 锚定短语面板：点进字段就在下方弹出对应槽位。排在最后 —— 它只挂事件，
     不依赖前面的初始化顺序。 --}}
<script src="{{ asset('include_js/phrase_panel.js') }}?v={{ filemtime(public_path('include_js/phrase_panel.js')) }}"></script>
{{-- 牙位软键盘：点行里的牙位格，网格贴着这一行弹出 --}}
<script src="{{ asset('include_js/tooth_pad.js') }}?v={{ filemtime(public_path('include_js/tooth_pad.js')) }}"></script>
@endsection
