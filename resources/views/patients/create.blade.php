{{--
    Patient Form Modal
    Design spec: 900px width, left-right split layout, grouped sections
    Left panel: avatar + tags checkboxes
    Right panel: form fields with collapsible sections
    Uses form-modal.css for common styles
--}}
<div class="modal fade modal-form modal-form-lg" id="patients-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="patient-modal-title">{{ __('patient.patient_form') }}</h4>
            </div>
            <div class="modal-body split-body">
                <form action="#" id="patient-form" class="form-horizontal" autocomplete="off" enctype="multipart/form-data" style="display:flex;width:100%;">
                    @csrf
                    <input type="hidden" id="patient_id" name="patient_id">
                    <input type="hidden" id="id" name="id">

                    {{-- ============================================================
                         Left Panel: Avatar + Tags
                         ============================================================ --}}
                    <div class="form-left-panel">
                        {{-- Avatar Upload --}}
                        <div class="avatar-upload-area">
                            <div class="avatar-upload-circle" id="avatar-upload-trigger">
                                <div class="avatar-placeholder" id="avatar-placeholder">
                                    <i class="fa fa-camera"></i>
                                    <span>{{ __('patient.upload_photo') }}</span>
                                </div>
                                <img id="avatar-preview" src="" alt="" style="display:none;">
                            </div>
                            <input type="file" id="photo_input" name="photo" accept="image/*" style="display:none;">
                            <div class="avatar-upload-label" id="avatar-change-label" style="display:none;">
                                {{ __('patient.change_photo') }}
                            </div>
                        </div>

                        {{-- Patient Tags (checkbox list) --}}
                        <div class="left-panel-section">
                            <div class="left-panel-section-title">{{ __('patient_tags.tags') }}</div>
                            <ul class="tag-checkbox-list" id="left-panel-tags">
                                {{-- Populated via AJAX --}}
                            </ul>
                        </div>

                        {{-- Patient Group (radio list, loaded from dict_items) --}}
                        <div class="left-panel-section">
                            <div class="left-panel-section-title">{{ __('patient.patient_group') }}</div>
                            <ul class="tag-checkbox-list" id="left-panel-groups">
                                <li><label><input type="radio" name="patient_group" value="" checked> {{ __('common.none') }}</label></li>
                                {{-- Populated via loadLeftPanelGroups() --}}
                            </ul>
                        </div>
                    </div>

                    {{-- ============================================================
                         Right Panel: Form Fields
                         ============================================================ --}}
                    <div class="form-right-main">
                        <div class="alert alert-danger" style="display:none">
                            <ul></ul>
                        </div>

                        {{-- Section 1: Basic Information --}}
                        @component('components.form.section', [
                            'id' => 'section-basic',
                            'title' => __('patient.basic_info'),
                            'icon' => 'fa-user'
                        ])
                            {{-- Row 1: Name + Phone + Gender (3 columns) --}}
                            <div class="form-row row">
                                @if(app()->getLocale() === 'zh-CN')
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label col-md-4">
                                                <span class="required-asterisk">*</span>{{ __('patient.full_name') }}
                                            </label>
                                            <div class="col-md-8">
                                                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="{{ __('patient.full_name') }}">
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label col-md-4">
                                                <span class="required-asterisk">*</span>{{ __('patient.surname') }}
                                            </label>
                                            <div class="col-md-8">
                                                <input type="text" name="surname" id="surname" class="form-control" placeholder="{{ __('patient.surname') }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    {{-- Phone field with intl-tel-input --}}
                                    <div class="form-group">
                                        <label class="control-label col-md-4">
                                            <span class="required-asterisk">*</span>{{ __('patient.phone_no') }}
                                        </label>
                                        <div class="col-md-8">
                                            <input type="text" id="telephone" name="telephone" class="form-control">
                                            <input type="hidden" id="phone_number" name="phone_no">
                                            <div class="validation-message" id="phone-validation"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    @include('components.form.radio-field', [
                                        'name' => 'gender',
                                        'label' => __('patient.gender'),
                                        'required' => true,
                                        'options' => [
                                            ['value' => 'Male', 'text' => __('patient.male')],
                                            ['value' => 'Female', 'text' => __('patient.female')],
                                        ],
                                    ])
                                </div>
                            </div>

                            @if(app()->getLocale() !== 'zh-CN')
                                {{-- Extra name field for non-Chinese locale --}}
                                <div class="form-row row">
                                    <div class="col-md-4">
                                        @include('components.form.text-field', [
                                            'name' => 'othername',
                                            'label' => __('patient.other_name'),
                                            'required' => true,
                                            'placeholder' => __('patient.other_name'),
                                        ])
                                    </div>
                                </div>
                            @endif

                            {{-- Row 2: ID Card + DOB + Source --}}
                            <div class="form-row row">
                                <div class="col-md-4">
                                    @include('components.form.text-field', [
                                        'name' => 'nin',
                                        'id' => 'id_card_input',
                                        'label' => __('patient.id_card'),
                                        'maxlength' => 18,
                                        'placeholder' => __('patient.id_card_placeholder'),
                                        'hint' => __('patient.id_card_hint'),
                                    ])
                                </div>
                                <div class="col-md-4">
                                    {{-- DOB with age display --}}
                                    <div class="form-group">
                                        <label class="control-label col-md-4">{{ __('patient.date_of_birth') }}</label>
                                        <div class="col-md-8">
                                            <div class="input-with-addon">
                                                <input type="text" name="dob" placeholder="yyyy-mm-dd" class="form-control" id="datepicker">
                                                <span id="age-display" class="age-display" style="display: none;"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    @include('components.form.select-field', [
                                        'name' => 'source_id',
                                        'label' => __('patient.source'),
                                        'select2' => true,
                                    ])
                                </div>
                                {{-- 建档日期：默认今天，补录纸质档案或旧系统患者时改成当年的日期。
                                     患者列表的日期筛选与新增患者报表都按这个字段算。 --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">{{ __('patient.registered_at') }}</label>
                                        <div class="col-md-8">
                                            <input type="text" name="registered_at" placeholder="yyyy-mm-dd"
                                                   class="form-control" id="registered_at_picker"
                                                   value="{{ now()->format('Y-m-d') }}">
                                            <span class="help-block">{{ __('patient.registered_at_hint') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 3: Referred By + Email + Address --}}
                            <div class="form-row row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">{{ __('patient.referred_by') }}</label>
                                        <div class="col-md-8">
                                            <select name="referred_by" id="referred_by" class="form-control"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    @include('components.form.text-field', [
                                        'name' => 'email',
                                        'label' => __('patient.email'),
                                        'type' => 'email',
                                        'placeholder' => __('patient.email_placeholder'),
                                    ])
                                </div>
                                <div class="col-md-4">
                                    @include('components.form.text-field', [
                                        'name' => 'address',
                                        'label' => __('patient.address'),
                                        'placeholder' => __('patient.address_placeholder'),
                                    ])
                                </div>
                            </div>
                        @endcomponent

                        {{-- Section 2: Demographics (collapsed by default) --}}
                        @component('components.form.section', [
                            'id' => 'section-demographics',
                            'title' => __('patient.demographics'),
                            'icon' => 'fa-id-card',
                            'collapsed' => true,
                            'hint' => __('common.optional')
                        ])
                            <div class="form-row row">
                                <div class="col-md-6">
                                    @include('components.form.text-field', [
                                        'name' => 'age',
                                        'label' => __('patient.age'),
                                        'type' => 'number',
                                        'placeholder' => __('patient.age_placeholder'),
                                        'hint' => __('patient.age_hint'),
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('components.form.text-field', [
                                        'name' => 'profession',
                                        'label' => __('patient.occupation'),
                                        'placeholder' => __('patient.occupation_placeholder'),
                                    ])
                                </div>
                            </div>
                            <div class="form-row row">
                                <div class="col-md-6">
                                    @include('components.form.select-field', [
                                        'name' => 'ethnicity',
                                        'label' => __('patient.ethnicity'),
                                        'options' => [
                                            ['value' => '', 'text' => __('common.please_select')],
                                            ['value' => 'han', 'text' => __('patient.ethnicity_han')],
                                            ['value' => 'zhuang', 'text' => __('patient.ethnicity_zhuang')],
                                            ['value' => 'hui', 'text' => __('patient.ethnicity_hui')],
                                            ['value' => 'manchu', 'text' => __('patient.ethnicity_manchu')],
                                            ['value' => 'uyghur', 'text' => __('patient.ethnicity_uyghur')],
                                            ['value' => 'miao', 'text' => __('patient.ethnicity_miao')],
                                            ['value' => 'yi', 'text' => __('patient.ethnicity_yi')],
                                            ['value' => 'tujia', 'text' => __('patient.ethnicity_tujia')],
                                            ['value' => 'tibetan', 'text' => __('patient.ethnicity_tibetan')],
                                            ['value' => 'mongol', 'text' => __('patient.ethnicity_mongol')],
                                            ['value' => 'other', 'text' => __('patient.ethnicity_other')],
                                        ],
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('components.form.select-field', [
                                        'name' => 'marital_status',
                                        'label' => __('patient.marital_status'),
                                        'options' => [
                                            ['value' => '', 'text' => __('common.please_select')],
                                            ['value' => 'single', 'text' => __('patient.marital_single')],
                                            ['value' => 'married', 'text' => __('patient.marital_married')],
                                            ['value' => 'divorced', 'text' => __('patient.marital_divorced')],
                                            ['value' => 'widowed', 'text' => __('patient.marital_widowed')],
                                            ['value' => 'other', 'text' => __('patient.marital_other')],
                                        ],
                                    ])
                                </div>
                            </div>
                            <div class="form-row row">
                                <div class="col-md-6">
                                    @include('components.form.select-field', [
                                        'name' => 'education',
                                        'label' => __('patient.education'),
                                        'options' => [
                                            ['value' => '', 'text' => __('common.please_select')],
                                            ['value' => 'primary', 'text' => __('patient.education_primary')],
                                            ['value' => 'junior_high', 'text' => __('patient.education_junior_high')],
                                            ['value' => 'senior_high', 'text' => __('patient.education_senior_high')],
                                            ['value' => 'college', 'text' => __('patient.education_college')],
                                            ['value' => 'bachelor', 'text' => __('patient.education_bachelor')],
                                            ['value' => 'master', 'text' => __('patient.education_master')],
                                            ['value' => 'doctor', 'text' => __('patient.education_doctor')],
                                            ['value' => 'other', 'text' => __('patient.education_other')],
                                        ],
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('components.form.select-field', [
                                        'name' => 'blood_type',
                                        'label' => __('patient.blood_type'),
                                        'options' => [
                                            ['value' => '', 'text' => __('common.please_select')],
                                            ['value' => 'A', 'text' => __('patient.blood_type_a')],
                                            ['value' => 'B', 'text' => __('patient.blood_type_b')],
                                            ['value' => 'AB', 'text' => __('patient.blood_type_ab')],
                                            ['value' => 'O', 'text' => __('patient.blood_type_o')],
                                            ['value' => 'A_Rh_negative', 'text' => __('patient.blood_type_a_rh_negative')],
                                            ['value' => 'B_Rh_negative', 'text' => __('patient.blood_type_b_rh_negative')],
                                            ['value' => 'AB_Rh_negative', 'text' => __('patient.blood_type_ab_rh_negative')],
                                            ['value' => 'O_Rh_negative', 'text' => __('patient.blood_type_o_rh_negative')],
                                            ['value' => 'unknown', 'text' => __('patient.blood_type_unknown')],
                                        ],
                                    ])
                                </div>
                            </div>
                        @endcomponent

                        {{-- Section 3: Health Information --}}
                        @component('components.form.section', [
                            'id' => 'section-health',
                            'title' => __('patient.health_info'),
                            'icon' => 'fa-heartbeat',
                            'collapsed' => true,
                            'hint' => __('common.optional')
                        ])
                            {{-- Drug Allergies --}}
                            <div class="form-row row">
                                <div class="col-md-12">
                                    @include('components.form.checkbox-field', [
                                        'name' => 'drug_allergies',
                                        'label' => __('patient.drug_allergy'),
                                        'labelWidth' => 2,
                                        'inputWidth' => 10,
                                        'options' => [
                                            ['value' => 'penicillin', 'text' => __('patient.allergy_penicillin')],
                                            ['value' => 'cephalosporin', 'text' => __('patient.allergy_cephalosporin')],
                                            ['value' => 'sulfa', 'text' => __('patient.allergy_sulfa')],
                                            ['value' => 'anesthetic', 'text' => __('patient.allergy_anesthetic')],
                                            ['value' => 'iodine', 'text' => __('patient.allergy_iodine')],
                                            ['value' => 'latex', 'text' => __('patient.allergy_latex')],
                                        ],
                                        'showOther' => true,
                                        'otherPlaceholder' => __('patient.other_allergy_placeholder'),
                                    ])
                                    <div class="col-md-offset-2 col-md-10">
                                        <div class="warning-box" id="allergy-warning" style="display: none;">
                                            <i class="fa fa-exclamation-triangle"></i> {{ __('patient.allergy_warning') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Systemic Diseases --}}
                            <div class="form-row row">
                                <div class="col-md-12">
                                    @include('components.form.checkbox-field', [
                                        'name' => 'systemic_diseases',
                                        'label' => __('patient.medical_history'),
                                        'labelWidth' => 2,
                                        'inputWidth' => 10,
                                        'options' => [
                                            ['value' => 'hypertension', 'text' => __('patient.disease_hypertension')],
                                            ['value' => 'diabetes', 'text' => __('patient.disease_diabetes')],
                                            ['value' => 'heart_disease', 'text' => __('patient.disease_heart')],
                                            ['value' => 'hepatitis', 'text' => __('patient.disease_hepatitis')],
                                            ['value' => 'infectious_disease', 'text' => __('patient.disease_infectious')],
                                            ['value' => 'blood_disease', 'text' => __('patient.disease_blood')],
                                        ],
                                        'showOther' => true,
                                        'otherPlaceholder' => __('patient.other_disease_placeholder'),
                                    ])
                                </div>
                            </div>

                            {{-- Current Medication --}}
                            <div class="form-row row">
                                <div class="col-md-12">
                                    @include('components.form.textarea-field', [
                                        'name' => 'current_medication',
                                        'label' => __('patient.current_medication'),
                                        'labelWidth' => 2,
                                        'inputWidth' => 10,
                                        'rows' => 2,
                                        'placeholder' => __('patient.current_medication_hint'),
                                    ])
                                </div>
                            </div>

                            {{-- Female-only: Pregnancy/Breastfeeding --}}
                            <div class="form-row row conditional-fields" id="female-special-conditions">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2">{{ __('patient.special_conditions') }}</label>
                                        <div class="col-md-10" style="padding-top: 7px;">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="is_pregnant" value="1"> {{ __('patient.is_pregnant') }}
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="is_breastfeeding" value="1"> {{ __('patient.is_breastfeeding') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcomponent

                        {{-- Section 4: Insurance Information (collapsed by default) --}}
                        @component('components.form.section', [
                            'id' => 'section-insurance',
                            'title' => __('patient.insurance_information'),
                            'icon' => 'fa-shield',
                            'collapsed' => true,
                            'hint' => __('common.optional')
                        ])
                            <div class="form-row row">
                                <div class="col-md-6">
                                    @include('components.form.radio-field', [
                                        'name' => 'has_insurance',
                                        'label' => __('patient.has_medical_insurance'),
                                        'options' => [
                                            ['value' => '1', 'text' => __('patient.has_insurance')],
                                            ['value' => '0', 'text' => __('patient.no_insurance')],
                                        ],
                                        'selected' => '0',
                                    ])
                                </div>
                                <div class="col-md-6 insurance_company" style="display: none;">
                                    @include('components.form.select-field', [
                                        'name' => 'insurance_company_id',
                                        'id' => 'company',
                                        'label' => __('patient.insurance_company'),
                                        'select2' => true,
                                    ])
                                </div>
                            </div>
                        @endcomponent

                        {{-- Section 5: Kin Relations --}}
                        @component('components.form.section', [
                            'id' => 'section-kin',
                            'title' => __('patient.kin_relations'),
                            'icon' => 'fa-users',
                            'collapsed' => true,
                            'hint' => __('common.optional')
                        ])
                            <div id="kin-relations-list">
                                {{-- Dynamic rows populated by JS --}}
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="button" class="btn btn-sm btn-default" onclick="addKinRelationRow()">
                                    <i class="fa fa-plus"></i> {{ __('patient.add_kin_relation') }}
                                </button>
                            </div>
                        @endcomponent

                        {{-- Section 6: Other Information --}}
                        @component('components.form.section', [
                            'id' => 'section-other',
                            'title' => __('patient.other_info'),
                            'icon' => 'fa-info-circle',
                            'collapsed' => true,
                            'hint' => __('common.optional')
                        ])
                            {{-- Row 1: Emergency Contact --}}
                            <div class="form-row row">
                                <div class="col-md-6">
                                    @include('components.form.text-field', [
                                        'name' => 'next_of_kin',
                                        'label' => __('patient.next_of_kin'),
                                        'placeholder' => __('patient.emergency_contact_name'),
                                    ])
                                </div>
                                <div class="col-md-6">
                                    @include('components.form.text-field', [
                                        'name' => 'next_of_kin_no',
                                        'label' => __('patient.next_of_kin_phone'),
                                    ])
                                </div>
                            </div>

                            {{-- Row 2: Notes --}}
                            <div class="form-row row">
                                <div class="col-md-12">
                                    @include('components.form.textarea-field', [
                                        'name' => 'notes',
                                        'label' => __('patient.notes'),
                                        'labelWidth' => 2,
                                        'inputWidth' => 10,
                                        'rows' => 2,
                                        'placeholder' => __('patient.notes_hint'),
                                    ])
                                </div>
                            </div>
                        @endcomponent

                    </div>{{-- /.form-right-main --}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" id="btnSaveAndContinue" class="btn btn-info" onclick="save_data(true)">
                    {{ __('common.save_and_continue') }}
                </button>
                <button type="button" id="btnSavePatient" class="btn btn-primary" onclick="save_data(false)">
                    {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 脚本已拆到 public/include_js/patients_create_modal.js（见 CLAUDE.md 资源拆分规范）。
     标签留在片段里而不是交给宿主页面：这个弹窗被 patients/index 与 today_work/index
     两处 @include，放在这儿才能保证谁引用谁就自带脚本，不会漏。 --}}
{{-- 只挂数据，不调用任何库：布局把 @yield('content') 放在 language-manager.js
     之前，这里直接调 LanguageManager 会是 ReferenceError。注册动作在 JS 里
     等 DOMContentLoaded 再做。 --}}
<script>
    window.PatientFormConfig = {
        locale: '{{ app()->getLocale() }}',
        lang: {
            'patient': @json(__('patient')),
            'members': @json(__('members'))
        }
    };
</script>
<script src="{{ asset('include_js/patients_create_modal.js') }}?v={{ filemtime(public_path('include_js/patients_create_modal.js')) }}"></script>
