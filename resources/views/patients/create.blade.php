{{--
    Patient Form Modal
    Design spec: 700px width, grouped sections, collapsible, field linkage
    Uses form-modal.css for common styles
--}}
<div class="modal fade modal-form" id="patients-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="patient-modal-title">{{ __('patient.patient_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="patient-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="patient_id" name="patient_id">
                    <input type="hidden" id="id" name="id">

                    {{-- Section 1: Basic Information --}}
                    @component('components.form.section', [
                        'id' => 'section-basic',
                        'title' => __('patient.basic_info'),
                        'icon' => 'fa-user'
                    ])
                        {{-- Row 1: Name fields (locale-adaptive) --}}
                        <div class="form-row row">
                            @if(app()->getLocale() === 'zh-CN')
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-4">
                                            <span class="required-asterisk">*</span>{{ __('patient.surname') }}
                                        </label>
                                        <div class="col-md-8">
                                            <input type="text" name="surname" id="surname" class="form-control" placeholder="{{ __('patient.surname') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @include('components.form.text-field', [
                                        'name' => 'othername',
                                        'label' => __('patient.other_name'),
                                        'required' => true,
                                        'placeholder' => __('patient.other_name'),
                                    ])
                                </div>
                            @endif
                        </div>

                        {{-- Row 2: Phone & ID Card (ID card first for auto-fill) --}}
                        <div class="form-row row">
                            <div class="col-md-6">
                                {{-- Custom phone field with intl-tel-input --}}
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
                            <div class="col-md-6">
                                @include('components.form.text-field', [
                                    'name' => 'nin',
                                    'id' => 'id_card_input',
                                    'label' => __('patient.id_card'),
                                    'maxlength' => 18,
                                    'placeholder' => __('patient.id_card_placeholder'),
                                    'hint' => __('patient.id_card_hint'),
                                ])
                            </div>
                        </div>

                        {{-- Row 3: Gender & Date of Birth (auto-filled from ID card) --}}
                        <div class="form-row row">
                            <div class="col-md-6">
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
                            <div class="col-md-6">
                                {{-- Custom DOB field with age display --}}
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
                        </div>

                        {{-- Row 4: Age & Profession --}}
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

                        {{-- Row 5: Ethnicity & Marital Status --}}
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

                        {{-- Row 5: Education & Blood Type --}}
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

                        {{-- Row 6: Email & Address --}}
                        <div class="form-row row">
                            <div class="col-md-6">
                                @include('components.form.text-field', [
                                    'name' => 'email',
                                    'label' => __('patient.email'),
                                    'type' => 'email',
                                    'placeholder' => __('patient.email_placeholder'),
                                ])
                            </div>
                            <div class="col-md-6">
                                @include('components.form.text-field', [
                                    'name' => 'address',
                                    'label' => __('patient.address'),
                                    'placeholder' => __('patient.address_placeholder'),
                                ])
                            </div>
                        </div>
                    @endcomponent

                    {{-- Section 2: Source Information (collapsed by default) --}}
                    @component('components.form.section', [
                        'id' => 'section-source',
                        'title' => __('patient_tags.source_info'),
                        'icon' => 'fa-share-alt',
                        'collapsed' => true,
                        'hint' => __('common.optional')
                    ])
                        <div class="form-row row">
                            <div class="col-md-6">
                                @include('components.form.select-field', [
                                    'name' => 'source_id',
                                    'label' => __('patient.source'),
                                    'select2' => true,
                                ])
                            </div>
                            <div class="col-md-6">
                                @include('components.form.select-field', [
                                    'name' => 'tags',
                                    'id' => 'patient_tags',
                                    'label' => __('patient.tags'),
                                    'multiple' => true,
                                    'select2' => true,
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
                                        ['value' => 'Yes', 'text' => __('patient.has_insurance')],
                                        ['value' => 'No', 'text' => __('patient.no_insurance')],
                                    ],
                                    'selected' => 'No',
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

                    {{-- Section 5: Other Information --}}
                    @component('components.form.section', [
                        'id' => 'section-other',
                        'title' => __('patient.other_info'),
                        'icon' => 'fa-info-circle',
                        'collapsed' => true,
                        'hint' => __('common.optional')
                    ])
                        {{-- Row 1: Alternative Phone & Next of Kin Name --}}
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

                        {{-- Row 3: Notes --}}
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
// Base Form Functions (from form-modal.blade.php)
// ==========================================================================

/**
 * Toggle form section collapse state
 */
function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.toggle('collapsed');
    if (toggle) {
        toggle.classList.toggle('collapsed');
    }
}

/**
 * Collapse a form section
 */
function collapseSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.add('collapsed');
    if (toggle) {
        toggle.classList.add('collapsed');
    }
}

/**
 * Expand a form section
 */
function expandSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var toggle = section.querySelector('.form-section-toggle');
    section.classList.remove('collapsed');
    if (toggle) {
        toggle.classList.remove('collapsed');
    }
}

/**
 * Reset form to initial state
 */
function resetForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return;

    form.reset();

    // Clear validation states
    var inputs = form.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.classList.remove('is-valid', 'is-invalid');
    });

    // Hide validation messages
    var validationMsgs = form.querySelectorAll('.validation-message');
    validationMsgs.forEach(function(msg) {
        msg.style.display = 'none';
    });

    // Reset Select2 fields
    $(form).find('select.select2, select[data-select2]').each(function() {
        $(this).val(null).trigger('change');
    });
}

/**
 * Calculate age from birthday
 */
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

/**
 * Validate email format
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Parse Chinese ID card to extract birthday and gender
 */
function parseChineseIdCard(idCard) {
    if (!idCard || idCard.length !== 18) return null;

    var year = idCard.substring(6, 10);
    var month = idCard.substring(10, 12);
    var day = idCard.substring(12, 14);
    var birthday = year + '-' + month + '-' + day;

    var genderCode = parseInt(idCard.substring(16, 17));
    var gender = (genderCode % 2 === 1) ? 'Male' : 'Female';

    return {
        birthday: birthday,
        gender: gender
    };
}

// ==========================================================================
// Form Mode Functions
// ==========================================================================

function resetPatientFormToCreateMode() {
    document.getElementById('patient-modal-title').textContent = '{{ __("patient.add_new_patient") }}';
    document.getElementById('btnSaveAndContinue').style.display = 'inline-block';

    // Reset form
    resetForm('patient-form');

    // Hide age display
    var ageDisplay = document.getElementById('age-display');
    if (ageDisplay) ageDisplay.style.display = 'none';

    // Collapse all optional sections for create mode
    collapseSection('section-source');
    collapseSection('section-health');
    collapseSection('section-insurance');
    collapseSection('section-other');

    // Reset visibility states
    updateFemaleFieldsVisibility();
    updateAllergyWarning();

    // Focus on first input field after modal is shown
    setTimeout(function() {
        var nameInput = document.getElementById('full_name') || document.getElementById('surname');
        if (nameInput) nameInput.focus();
    }, 300);
}

function setPatientFormToEditMode(patient) {
    document.getElementById('patient-modal-title').textContent = '{{ __("patient.edit_patient") }}';
    document.getElementById('btnSaveAndContinue').style.display = 'none';

    // Update age display if DOB exists
    var dobInput = document.querySelector('[name="dob"]');
    if (dobInput && dobInput.value) {
        updateAgeDisplay(dobInput.value);
    }

    // Auto-expand sections based on patient data
    if (patient) {
        // Expand Source section if patient has source/tags
        if (patient.source_id || (patient.tags && patient.tags.length > 0)) {
            expandSection('section-source');
        }

        // Expand Health section if patient has health info
        if (patientHasHealthInfo(patient)) {
            expandSection('section-health');
        }

        // Expand Insurance section if patient has insurance
        if (patient.has_insurance === 'Yes' || patient.insurance_company_id) {
            expandSection('section-insurance');
        }

        // Expand Other section if patient has other info
        if (patient.profession || patient.alternative_no || patient.next_of_kin || patient.notes) {
            expandSection('section-other');
        }
    }
}

/**
 * Check if patient has any health information
 */
function patientHasHealthInfo(patient) {
    if (!patient) return false;

    // Check drug allergies
    if (patient.drug_allergies && patient.drug_allergies.length > 0) return true;
    if (patient.drug_allergies_other) return true;

    // Check systemic diseases
    if (patient.systemic_diseases && patient.systemic_diseases.length > 0) return true;
    if (patient.systemic_diseases_other) return true;

    // Check other health fields
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
                // Auto-fill birthday if empty
                var dobInput = document.querySelector('[name="dob"]');
                if (dobInput && !dobInput.value) {
                    dobInput.value = parsed.birthday;
                    updateAgeDisplay(parsed.birthday);
                }
                // Auto-select gender
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
        // Clear values when hiding
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

    // Check checkboxes
    var checkboxes = document.querySelectorAll('input[name="drug_allergies[]"]');
    checkboxes.forEach(function(cb) {
        if (cb.checked) hasAllergy = true;
    });

    // Check other input
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
    // Drug allergies
    if (patient.drug_allergies && Array.isArray(patient.drug_allergies)) {
        document.querySelectorAll('input[name="drug_allergies[]"]').forEach(function(cb) {
            cb.checked = patient.drug_allergies.includes(cb.value);
        });
    }

    var allergyOther = document.querySelector('input[name="drug_allergies_other"]');
    if (allergyOther) allergyOther.value = patient.drug_allergies_other || '';

    // Systemic diseases
    if (patient.systemic_diseases && Array.isArray(patient.systemic_diseases)) {
        document.querySelectorAll('input[name="systemic_diseases[]"]').forEach(function(cb) {
            cb.checked = patient.systemic_diseases.includes(cb.value);
        });
    }

    var diseaseOther = document.querySelector('input[name="systemic_diseases_other"]');
    if (diseaseOther) diseaseOther.value = patient.systemic_diseases_other || '';

    // Current medication
    var medication = document.querySelector('textarea[name="current_medication"]');
    if (medication) medication.value = patient.current_medication || '';

    // Pregnancy/breastfeeding
    var pregnant = document.querySelector('input[name="is_pregnant"]');
    if (pregnant) pregnant.checked = patient.is_pregnant == 1;

    var breastfeeding = document.querySelector('input[name="is_breastfeeding"]');
    if (breastfeeding) breastfeeding.checked = patient.is_breastfeeding == 1;

    // Update visibility
    updateFemaleFieldsVisibility();
    updateAllergyWarning();
}

function clearHealthInfo() {
    // Clear all health-related checkboxes
    document.querySelectorAll('input[name="drug_allergies[]"], input[name="systemic_diseases[]"]').forEach(function(cb) {
        cb.checked = false;
    });

    // Clear other inputs
    var otherInputs = ['drug_allergies_other', 'systemic_diseases_other'];
    otherInputs.forEach(function(name) {
        var input = document.querySelector('input[name="' + name + '"]');
        if (input) input.value = '';
    });

    // Clear textarea
    var medication = document.querySelector('textarea[name="current_medication"]');
    if (medication) medication.value = '';

    // Clear pregnancy/breastfeeding
    var pregnant = document.querySelector('input[name="is_pregnant"]');
    if (pregnant) pregnant.checked = false;

    var breastfeeding = document.querySelector('input[name="is_breastfeeding"]');
    if (breastfeeding) breastfeeding.checked = false;

    // Update visibility
    updateFemaleFieldsVisibility();
    updateAllergyWarning();
}

// ==========================================================================
// Initialize
// ==========================================================================

document.addEventListener('DOMContentLoaded', function() {
    initIdCardParsing();
    initEmailValidation();
    initGenderVisibility();
    initAllergyWarning();
    initDobHandler();
});
</script>
