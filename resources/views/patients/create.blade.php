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
                <button type="button" id="btnSave" class="btn btn-primary" onclick="save_data(false)">
                    {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================================================
// Base Form Functions
// ==========================================================================

function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    var toggle = section.querySelector('.form-section-toggle');
    section.classList.toggle('collapsed');
    if (toggle) toggle.classList.toggle('collapsed');
}

function collapseSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    var toggle = section.querySelector('.form-section-toggle');
    section.classList.add('collapsed');
    if (toggle) toggle.classList.add('collapsed');
}

function expandSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    var toggle = section.querySelector('.form-section-toggle');
    section.classList.remove('collapsed');
    if (toggle) toggle.classList.remove('collapsed');
}

function resetForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    form.reset();

    var inputs = form.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.classList.remove('is-valid', 'is-invalid');
    });

    var validationMsgs = form.querySelectorAll('.validation-message');
    validationMsgs.forEach(function(msg) {
        msg.style.display = 'none';
    });

    // Reset Select2 fields
    $(form).find('select.select2, select[data-select2]').each(function() {
        $(this).val(null).trigger('change');
    });

    // Reset avatar
    resetAvatar();

    // Uncheck all tag checkboxes in left panel
    document.querySelectorAll('#left-panel-tags input[type="checkbox"]').forEach(function(cb) {
        cb.checked = false;
    });
}

function calculateAge(birthday) {
    if (!birthday) return null;
    var today = new Date();
    var birthDate = new Date(birthday);
    var age = today.getFullYear() - birthDate.getFullYear();
    var monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return (age >= 0 && age < 150) ? age : null;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function parseChineseIdCard(idCard) {
    if (!idCard || idCard.length !== 18) return null;
    var year = idCard.substring(6, 10);
    var month = idCard.substring(10, 12);
    var day = idCard.substring(12, 14);
    var birthday = year + '-' + month + '-' + day;
    var genderCode = parseInt(idCard.substring(16, 17));
    var gender = (genderCode % 2 === 1) ? 'Male' : 'Female';
    return { birthday: birthday, gender: gender };
}

// ==========================================================================
// Avatar Upload
// ==========================================================================

function initAvatarUpload() {
    var trigger = document.getElementById('avatar-upload-trigger');
    var fileInput = document.getElementById('photo_input');
    var preview = document.getElementById('avatar-preview');
    var placeholder = document.getElementById('avatar-placeholder');
    var changeLabel = document.getElementById('avatar-change-label');

    if (!trigger || !fileInput) return;

    trigger.addEventListener('click', function() {
        fileInput.click();
    });

    if (changeLabel) {
        changeLabel.addEventListener('click', function() {
            fileInput.click();
        });
    }

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                if (changeLabel) changeLabel.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

function resetAvatar() {
    var preview = document.getElementById('avatar-preview');
    var placeholder = document.getElementById('avatar-placeholder');
    var changeLabel = document.getElementById('avatar-change-label');
    var fileInput = document.getElementById('photo_input');

    if (preview) { preview.src = ''; preview.style.display = 'none'; }
    if (placeholder) placeholder.style.display = '';
    if (changeLabel) changeLabel.style.display = 'none';
    if (fileInput) fileInput.value = '';
}

function setAvatarFromUrl(url) {
    if (!url) return;
    var preview = document.getElementById('avatar-preview');
    var placeholder = document.getElementById('avatar-placeholder');
    var changeLabel = document.getElementById('avatar-change-label');

    if (preview) { preview.src = url; preview.style.display = 'block'; }
    if (placeholder) placeholder.style.display = 'none';
    if (changeLabel) changeLabel.style.display = 'block';
}

// ==========================================================================
// Left Panel Tags
// ==========================================================================

var _allTags = [];
var _tagsLoaded = false;
var _pendingTagIds = null;

function loadLeftPanelTags(callback) {
    $.get('/patient-tags-list', function(data) {
        _allTags = data;
        _tagsLoaded = true;
        renderLeftPanelTags(data);
        // Apply pending tag selection if any
        if (_pendingTagIds) {
            setLeftPanelTags(_pendingTagIds);
            _pendingTagIds = null;
        }
        if (typeof callback === 'function') callback();
    });
}

// ==========================================================================
// Left Panel Groups (from dict_items)
// ==========================================================================

var _allGroups = [];
var _groupsLoaded = false;

function loadLeftPanelGroups() {
    $.get('/dict-items-list', {type: 'patient_group'}, function(data) {
        _allGroups = data;
        _groupsLoaded = true;
        renderLeftPanelGroups(data);
    });
}

function renderLeftPanelGroups(groups) {
    var container = document.getElementById('left-panel-groups');
    if (!container) return;
    // Keep the "none" radio, remove the rest
    var noneItem = container.querySelector('li:first-child');
    container.innerHTML = '';
    if (noneItem) container.appendChild(noneItem);

    groups.forEach(function(group) {
        var li = document.createElement('li');
        var label = document.createElement('label');
        var radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'patient_group';
        radio.value = group.id; // group.id is actually the code
        label.appendChild(radio);
        label.appendChild(document.createTextNode(' ' + group.text));
        li.appendChild(label);
        container.appendChild(li);
    });
}

function renderLeftPanelTags(tags) {
    var container = document.getElementById('left-panel-tags');
    if (!container) return;
    container.innerHTML = '';

    tags.forEach(function(tag) {
        var li = document.createElement('li');
        var label = document.createElement('label');
        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'tags[]';
        checkbox.value = tag.id;

        label.appendChild(checkbox);

        if (tag.color) {
            var dot = document.createElement('span');
            dot.className = 'tag-color-dot';
            dot.style.backgroundColor = tag.color;
            label.appendChild(dot);
        }

        label.appendChild(document.createTextNode(' ' + tag.text));
        li.appendChild(label);
        container.appendChild(li);
    });
}

function setLeftPanelTags(tagIds) {
    if (!tagIds || !Array.isArray(tagIds)) return;
    if (!_tagsLoaded) {
        // Tags not yet loaded, queue for later
        _pendingTagIds = tagIds;
        return;
    }
    document.querySelectorAll('#left-panel-tags input[type="checkbox"]').forEach(function(cb) {
        cb.checked = tagIds.indexOf(parseInt(cb.value)) !== -1 || tagIds.indexOf(cb.value) !== -1;
    });
}

// ==========================================================================
// Form Mode Functions
// ==========================================================================

function resetPatientFormToCreateMode() {
    document.getElementById('patient-modal-title').textContent = '{{ __("patient.add_new_patient") }}';
    document.getElementById('btnSaveAndContinue').style.display = 'inline-block';

    resetForm('patient-form');

    var ageDisplay = document.getElementById('age-display');
    if (ageDisplay) ageDisplay.style.display = 'none';

    // Collapse all optional sections
    collapseSection('section-demographics');
    collapseSection('section-health');
    collapseSection('section-insurance');
    collapseSection('section-kin');
    collapseSection('section-other');

    // Clear kin relations
    clearKinRelations();

    updateFemaleFieldsVisibility();
    updateAllergyWarning();

    setTimeout(function() {
        var nameInput = document.getElementById('full_name') || document.getElementById('surname');
        if (nameInput) nameInput.focus();
    }, 300);
}

function setPatientFormToEditMode(patient) {
    document.getElementById('patient-modal-title').textContent = '{{ __("patient.edit_patient") }}';
    document.getElementById('btnSaveAndContinue').style.display = 'none';

    var dobInput = document.querySelector('[name="dob"]');
    if (dobInput && dobInput.value) {
        updateAgeDisplay(dobInput.value);
    }

    // Set avatar if patient has photo
    if (patient && patient.photo) {
        setAvatarFromUrl(patient.photo);
    }

    // Set tags in left panel
    if (patient && patient.tags) {
        var tagIds = patient.tags.map(function(t) { return typeof t === 'object' ? t.id : t; });
        setLeftPanelTags(tagIds);
    }

    // Set patient group radio
    if (patient && patient.patient_group) {
        var groupRadio = document.querySelector('input[name="patient_group"][value="' + patient.patient_group + '"]');
        if (groupRadio) groupRadio.checked = true;
    } else {
        var noneRadio = document.querySelector('input[name="patient_group"][value=""]');
        if (noneRadio) noneRadio.checked = true;
    }

    if (patient) {
        // Expand Demographics if has data
        if (patient.profession || patient.ethnicity || patient.marital_status || patient.education || patient.blood_type) {
            expandSection('section-demographics');
        }

        if (patientHasHealthInfo(patient)) {
            expandSection('section-health');
        }

        if (patient.has_insurance == 1 || patient.insurance_company_id) {
            expandSection('section-insurance');
        }

        if (patient.next_of_kin || patient.notes) {
            expandSection('section-other');
        }
    }
}

function patientHasHealthInfo(patient) {
    if (!patient) return false;
    if (patient.drug_allergies && patient.drug_allergies.length > 0) return true;
    if (patient.drug_allergies_other) return true;
    if (patient.systemic_diseases && patient.systemic_diseases.length > 0) return true;
    if (patient.systemic_diseases_other) return true;
    if (patient.current_medication) return true;
    if (patient.is_pregnant == 1) return true;
    if (patient.is_breastfeeding == 1) return true;
    return false;
}

// ==========================================================================
// Age Display
// ==========================================================================

function updateAgeDisplay(birthday) {
    var ageSpan = document.getElementById('age-display');
    if (!ageSpan) return;
    var age = calculateAge(birthday);
    if (age !== null) {
        ageSpan.textContent = age + ' {{ __("common.years_old") }}';
        ageSpan.style.display = 'inline-block';
    } else {
        ageSpan.style.display = 'none';
    }
}

// ==========================================================================
// ID Card Parsing
// ==========================================================================

function initIdCardParsing() {
    var idCardInput = document.getElementById('id_card_input');
    if (!idCardInput) return;

    idCardInput.addEventListener('blur', function() {
        var idCard = this.value.trim();
        if (idCard.length === 18) {
            var parsed = parseChineseIdCard(idCard);
            if (parsed) {
                var dobInput = document.querySelector('[name="dob"]');
                if (dobInput && !dobInput.value) {
                    dobInput.value = parsed.birthday;
                    updateAgeDisplay(parsed.birthday);
                }
                var genderRadio = document.querySelector('input[name="gender"][value="' + parsed.gender + '"]');
                if (genderRadio) {
                    genderRadio.checked = true;
                    updateFemaleFieldsVisibility();
                }
            }
        }
    });
}

// ==========================================================================
// Email Validation
// ==========================================================================

function initEmailValidation() {
    var emailInput = document.querySelector('[name="email"]');
    if (!emailInput) return;

    emailInput.addEventListener('blur', function() {
        var email = this.value.trim();
        var validationDiv = document.getElementById('email-validation');

        if (email && !isValidEmail(email)) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
            if (validationDiv) {
                validationDiv.textContent = '{{ __("common.invalid_email") }}';
                validationDiv.className = 'validation-message error';
                validationDiv.style.display = 'block';
            }
        } else if (email) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
            if (validationDiv) validationDiv.style.display = 'none';
        } else {
            this.classList.remove('is-valid', 'is-invalid');
            if (validationDiv) validationDiv.style.display = 'none';
        }
    });
}

// ==========================================================================
// Gender-based Visibility
// ==========================================================================

function updateFemaleFieldsVisibility() {
    var femaleFields = document.getElementById('female-special-conditions');
    if (!femaleFields) return;

    var femaleRadio = document.querySelector('input[name="gender"][value="Female"]');
    var isFemale = femaleRadio && femaleRadio.checked;

    if (isFemale) {
        femaleFields.classList.add('show');
    } else {
        femaleFields.classList.remove('show');
        var checkboxes = femaleFields.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(cb) { cb.checked = false; });
    }
}

function initGenderVisibility() {
    var genderRadios = document.querySelectorAll('input[name="gender"]');
    genderRadios.forEach(function(radio) {
        radio.addEventListener('change', updateFemaleFieldsVisibility);
    });
    updateFemaleFieldsVisibility();
}

// ==========================================================================
// Allergy Warning
// ==========================================================================

function updateAllergyWarning() {
    var allergyWarning = document.getElementById('allergy-warning');
    if (!allergyWarning) return;

    var hasAllergy = false;
    var checkboxes = document.querySelectorAll('input[name="drug_allergies[]"]');
    checkboxes.forEach(function(cb) {
        if (cb.checked) hasAllergy = true;
    });

    var otherInput = document.querySelector('input[name="drug_allergies_other"]');
    if (otherInput && otherInput.value.trim()) hasAllergy = true;

    allergyWarning.style.display = hasAllergy ? 'block' : 'none';
}

function initAllergyWarning() {
    var checkboxes = document.querySelectorAll('input[name="drug_allergies[]"]');
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateAllergyWarning);
    });

    var otherInput = document.querySelector('input[name="drug_allergies_other"]');
    if (otherInput) {
        otherInput.addEventListener('input', updateAllergyWarning);
    }

    updateAllergyWarning();
}

// ==========================================================================
// DOB Change Handler
// ==========================================================================

function initDobHandler() {
    var dobInput = document.querySelector('[name="dob"]');
    if (dobInput) {
        dobInput.addEventListener('change', function() {
            updateAgeDisplay(this.value);
        });
    }
}

// ==========================================================================
// Health Info Population (for edit mode)
// ==========================================================================

function populateHealthInfo(patient) {
    if (patient.drug_allergies && Array.isArray(patient.drug_allergies)) {
        document.querySelectorAll('input[name="drug_allergies[]"]').forEach(function(cb) {
            cb.checked = patient.drug_allergies.includes(cb.value);
        });
    }

    var allergyOther = document.querySelector('input[name="drug_allergies_other"]');
    if (allergyOther) allergyOther.value = patient.drug_allergies_other || '';

    if (patient.systemic_diseases && Array.isArray(patient.systemic_diseases)) {
        document.querySelectorAll('input[name="systemic_diseases[]"]').forEach(function(cb) {
            cb.checked = patient.systemic_diseases.includes(cb.value);
        });
    }

    var diseaseOther = document.querySelector('input[name="systemic_diseases_other"]');
    if (diseaseOther) diseaseOther.value = patient.systemic_diseases_other || '';

    var medication = document.querySelector('textarea[name="current_medication"]');
    if (medication) medication.value = patient.current_medication || '';

    var pregnant = document.querySelector('input[name="is_pregnant"]');
    if (pregnant) pregnant.checked = patient.is_pregnant == 1;

    var breastfeeding = document.querySelector('input[name="is_breastfeeding"]');
    if (breastfeeding) breastfeeding.checked = patient.is_breastfeeding == 1;

    updateFemaleFieldsVisibility();
    updateAllergyWarning();
}

function clearHealthInfo() {
    document.querySelectorAll('input[name="drug_allergies[]"], input[name="systemic_diseases[]"]').forEach(function(cb) {
        cb.checked = false;
    });

    var otherInputs = ['drug_allergies_other', 'systemic_diseases_other'];
    otherInputs.forEach(function(name) {
        var input = document.querySelector('input[name="' + name + '"]');
        if (input) input.value = '';
    });

    var medication = document.querySelector('textarea[name="current_medication"]');
    if (medication) medication.value = '';

    var pregnant = document.querySelector('input[name="is_pregnant"]');
    if (pregnant) pregnant.checked = false;

    var breastfeeding = document.querySelector('input[name="is_breastfeeding"]');
    if (breastfeeding) breastfeeding.checked = false;

    updateFemaleFieldsVisibility();
    updateAllergyWarning();
}

// ==========================================================================
// Kin Relations (inline add/remove)
// ==========================================================================

var _kinRowIndex = 0;

function addKinRelationRow(data) {
    var container = document.getElementById('kin-relations-list');
    if (!container) return;

    var idx = _kinRowIndex++;
    var row = document.createElement('div');
    row.className = 'form-row row kin-relation-row';
    row.setAttribute('data-kin-index', idx);
    row.style.marginBottom = '10px';

    row.innerHTML =
        '<div class="col-md-5">' +
            '<select name="kin_relations[' + idx + '][patient_id]" class="form-control kin-patient-select" data-idx="' + idx + '"></select>' +
        '</div>' +
        '<div class="col-md-3">' +
            '<select name="kin_relations[' + idx + '][relationship]" class="form-control">' +
                '<option value="">{{ __("common.please_select") }}</option>' +
                '<option value="spouse">{{ __("members.relationship_spouse") }}</option>' +
                '<option value="child">{{ __("members.relationship_child") }}</option>' +
                '<option value="parent">{{ __("members.relationship_parent") }}</option>' +
                '<option value="other">{{ __("members.relationship_other") }}</option>' +
            '</select>' +
        '</div>' +
        '<div class="col-md-3">' +
            '<input type="text" name="kin_relations[' + idx + '][notes]" class="form-control" placeholder="{{ __("patient.notes") }}">' +
        '</div>' +
        '<div class="col-md-1">' +
            '<button type="button" class="btn btn-sm btn-danger" onclick="removeKinRelationRow(' + idx + ')" title="{{ __("common.delete") }}">' +
                '<i class="fa fa-trash"></i>' +
            '</button>' +
        '</div>';

    container.appendChild(row);

    // Init Select2 for patient search
    var select = $(row).find('.kin-patient-select');
    select.select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("patient.kin_search_placeholder") }}',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '/patients/filter',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(results) {
                return {
                    results: results.map(function(item) {
                        return { id: item.id, text: item.full_name + (item.phone_no ? ' (' + item.phone_no + ')' : '') };
                    })
                };
            },
            cache: true
        }
    });

    // Pre-fill if data provided (edit mode)
    if (data && data.patient_id) {
        var option = new Option(data.patient_name || ('Patient #' + data.patient_id), data.patient_id, true, true);
        select.append(option).trigger('change');
        if (data.relationship) {
            $(row).find('select[name*="relationship"]').val(data.relationship);
        }
        if (data.notes) {
            $(row).find('input[name*="notes"]').val(data.notes);
        }
    }
}

function removeKinRelationRow(idx) {
    var row = document.querySelector('.kin-relation-row[data-kin-index="' + idx + '"]');
    if (row) {
        $(row).find('.kin-patient-select').select2('destroy');
        row.remove();
    }
}

function clearKinRelations() {
    var container = document.getElementById('kin-relations-list');
    if (container) {
        $(container).find('.kin-patient-select').each(function() {
            $(this).select2('destroy');
        });
        container.innerHTML = '';
    }
    _kinRowIndex = 0;
}

function loadKinRelations(sharedHolders) {
    clearKinRelations();
    if (!sharedHolders || !Array.isArray(sharedHolders)) return;

    sharedHolders.forEach(function(holder) {
        var sharedPatient = holder.shared_patient || {};
        addKinRelationRow({
            patient_id: holder.shared_patient_id,
            patient_name: sharedPatient.full_name || (sharedPatient.surname + ' ' + sharedPatient.othername),
            relationship: holder.relationship,
            notes: ''
        });
    });

    if (sharedHolders.length > 0) {
        expandSection('section-kin');
    }
}

// ==========================================================================
// FormData Builder (replaces serialize() to support file upload)
// ==========================================================================

function buildPatientFormData() {
    var form = document.getElementById('patient-form');
    var formData = new FormData(form);

    // intl-tel-input phone number
    if (typeof iti !== 'undefined') {
        formData.set('phone_no', iti.getNumber());
    }

    return formData;
}

// ==========================================================================
// Initialize
// ==========================================================================

document.addEventListener('DOMContentLoaded', function() {
    initAvatarUpload();
    initIdCardParsing();
    initEmailValidation();
    initGenderVisibility();
    initAllergyWarning();
    initDobHandler();
    loadLeftPanelTags();
    loadLeftPanelGroups();
    initReferredBySelect();
});

function initReferredBySelect() {
    $('#referred_by').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("patient.referred_by_placeholder") }}',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '/search-patient',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });
}

function setReferredBy(patientId, patientName) {
    if (!patientId) {
        $('#referred_by').val(null).trigger('change');
        return;
    }
    var option = new Option(patientName, patientId, true, true);
    $('#referred_by').append(option).trigger('change');
}
</script>
