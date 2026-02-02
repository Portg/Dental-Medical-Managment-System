/**
 * Medical Record Edit Form JavaScript
 * F-MED-001: SOAP Medical Record
 */

// ==========================================================================
// Global Variables
// ==========================================================================

var selectedPatientData = null;
var currentToothField = 'related';

// ==========================================================================
// Initialization
// ==========================================================================

$(document).ready(function() {
    initPatientSelector();
    initDoctorSelect();
    initCharacterCounter();
    initQuickPhrases();
    initToothMiniChart();
    initTemplatePicker();
    updateMiniChartHighlights();
});

/**
 * Initialize patient selector with AJAX search (for create mode)
 */
function initPatientSelector() {
    if (typeof needPatientSelection === 'undefined' || !needPatientSelection) {
        return;
    }

    $('#patient_selector').select2({
        placeholder: MedicalRecordConfig.translations.searchAndSelectPatient,
        allowClear: true,
        width: '100%',
        minimumInputLength: 1,
        language: {
            inputTooShort: function() {
                return MedicalRecordConfig.translations.typeToSearch;
            },
            noResults: function() {
                return MedicalRecordConfig.translations.noResults;
            },
            searching: function() {
                return MedicalRecordConfig.translations.searching;
            }
        },
        ajax: {
            url: MedicalRecordConfig.urls.searchPatient,
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term,
                    full: 1
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(patient) {
                        return {
                            id: patient.id,
                            text: (patient.patient_no || '') + ' - ' + LanguageManager.joinName(patient.surname, patient.othername),
                            patient: patient
                        };
                    })
                };
            },
            cache: true
        }
    }).on('select2:select', function(e) {
        var data = e.params.data;
        if (data && data.patient) {
            selectedPatientData = {
                id: data.patient.id,
                surname: data.patient.surname,
                othername: data.patient.othername,
                gender: data.patient.gender,
                dob: data.patient.dob,
                patient_no: data.patient.patient_no,
                allergies: data.patient.drug_allergies_other || '',
                chronic: data.patient.chronic_diseases || ''
            };
            enableFormWithPatient();
        }
    }).on('select2:clear', function() {
        selectedPatientData = null;
        disableFormWithoutPatient();
    });
}

/**
 * Initialize doctor select
 */
function initDoctorSelect() {
    $('#doctor_id').select2({
        placeholder: MedicalRecordConfig.translations.selectDoctor,
        allowClear: true
    });
}

/**
 * Initialize character counter for chief complaint
 */
function initCharacterCounter() {
    $('#chief_complaint').on('input', function() {
        $('#chief_complaint_count').text($(this).val().length);
    });
}

/**
 * Initialize quick phrase insertion (using event delegation)
 */
function initQuickPhrases() {
    // Use event delegation to handle clicks on .quick-phrase elements
    $(document).on('click', '.quick-phrase', function(e) {
        e.preventDefault();

        // Check if panel is disabled
        if ($(this).closest('.sidebar-tool-panel').hasClass('disabled')) {
            return;
        }

        var phrase = $(this).data('phrase');
        if (!phrase) return;

        var $focused = $(':focus');
        if ($focused.is('textarea')) {
            var curPos = $focused[0].selectionStart;
            var textBefore = $focused.val().substring(0, curPos);
            var textAfter = $focused.val().substring(curPos);
            $focused.val(textBefore + phrase + textAfter);
            $focused[0].selectionStart = $focused[0].selectionEnd = curPos + phrase.length;
            $focused.focus();
        } else {
            var $exam = $('#examination');
            $exam.val($exam.val() + phrase);
            $exam.focus();
        }
    });
}

/**
 * Initialize tooth chart tab switching (permanent / deciduous)
 */
function initToothChartTabs() {
    $(document).on('click', '.tooth-chart-tab', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        // Switch active tab
        $(this).siblings('.tooth-chart-tab').removeClass('active');
        $(this).addClass('active');
        // Switch panel
        $(this).closest('.portlet-body').find('.tooth-chart-panel').hide();
        $('#tooth-panel-' + target).show();
    });
}

/**
 * Initialize tooth mini chart click events (using event delegation)
 * Sidebar mini chart operates on examination teeth, with downstream sync to diagnosis
 */
function initToothMiniChart() {
    initToothChartTabs();

    $(document).on('click', '.tooth-mini', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Check if panel is disabled
        if ($(this).closest('.sidebar-tool-panel').hasClass('disabled')) {
            return;
        }

        var tooth = $(this).data('tooth');
        if (!tooth) return;
        var toothStr = tooth.toString();

        // Check if tooth is in examination field
        var examTeeth = JSON.parse($('#examination_teeth').val() || '[]');
        var isSelected = examTeeth.indexOf(toothStr) !== -1;

        if (isSelected) {
            removeToothFromField('examination', tooth);
        } else {
            addToothToField('examination', tooth);
        }

        updateMiniChartHighlights();
    });
}

/**
 * Add tooth to a specific field, with one-way downstream sync:
 *   examination → diagnosis (downstream)
 *   diagnosis → nothing (no upstream sync)
 */
function addToothToField(field, tooth) {
    var toothStr = tooth.toString();

    // Add to the target field
    _addToothUI(field, toothStr);

    // One-way downstream: examination → diagnosis
    if (field === 'examination') {
        _addToothUI('related', toothStr);
    }
}

/**
 * Remove tooth from a specific field, with one-way downstream sync:
 *   examination → diagnosis (downstream)
 *   diagnosis → nothing (no upstream sync)
 */
function removeToothFromField(field, tooth) {
    var toothStr = tooth.toString();

    // Remove from the target field
    _removeToothUI(field, toothStr);

    // One-way downstream: examination → diagnosis
    if (field === 'examination') {
        _removeToothUI('related', toothStr);
    }
}

/**
 * Internal: add tooth to a single field's hidden input and UI tags
 */
function _addToothUI(field, toothStr) {
    var inputId = (field === 'related') ? '#related_teeth' : '#examination_teeth';
    var tagsId = (field === 'related') ? '#related-teeth-tags' : '#examination-teeth-tags';

    var teeth = JSON.parse($(inputId).val() || '[]');
    if (teeth.indexOf(toothStr) === -1) {
        teeth.push(toothStr);
        $(inputId).val(JSON.stringify(teeth));
        var tag = '<span class="tooth-tag" data-tooth="' + toothStr + '">' + toothStr +
                  '<span class="remove-tooth" onclick="removeTooth(\'' + field + '\', \'' + toothStr + '\')">&times;</span></span>';
        $(tagsId).find('.add-teeth-btn').before(tag);
    }
}

/**
 * Internal: remove tooth from a single field's hidden input and UI tags
 */
function _removeToothUI(field, toothStr) {
    var inputId = (field === 'related') ? '#related_teeth' : '#examination_teeth';
    var tagsId = (field === 'related') ? '#related-teeth-tags' : '#examination-teeth-tags';

    var teeth = JSON.parse($(inputId).val() || '[]');
    var idx = teeth.indexOf(toothStr);
    if (idx !== -1) {
        teeth.splice(idx, 1);
        $(inputId).val(JSON.stringify(teeth));
        $(tagsId + ' .tooth-tag[data-tooth="' + toothStr + '"]').remove();
    }
}

// ==========================================================================
// Tooth Management
// ==========================================================================

/**
 * Open tooth selector modal
 */
function openToothSelector(field) {
    currentToothField = field;
    $('#tooth_selector_modal').modal('show');
}

/**
 * Add a tooth from a specific field context (called by UI tag buttons)
 */
function addTooth(field, tooth) {
    addToothToField(field, tooth);
    updateMiniChartHighlights();
}

/**
 * Remove a tooth from a specific field context (called by UI tag ×buttons)
 */
function removeTooth(field, tooth) {
    removeToothFromField(field, tooth);
    updateMiniChartHighlights();
}

/**
 * Update mini chart highlights based on examination teeth
 * (sidebar mini chart reflects examination state)
 */
function updateMiniChartHighlights() {
    var examTeeth = JSON.parse($('#examination_teeth').val() || '[]');
    var allSelected = examTeeth;

    var hasPermanent = false;
    var hasDeciduous = false;

    $('.tooth-mini').each(function() {
        var tooth = $(this).data('tooth').toString();
        if (allSelected.indexOf(tooth) !== -1) {
            $(this).addClass('selected');
            if ($(this).hasClass('deciduous')) {
                hasDeciduous = true;
            } else {
                hasPermanent = true;
            }
        } else {
            $(this).removeClass('selected');
        }
    });

    // Show dot badge on inactive tab if that panel has selections
    $('.tooth-chart-tab').each(function() {
        var target = $(this).data('target');
        var hasSelection = (target === 'permanent') ? hasPermanent : hasDeciduous;
        if (hasSelection && !$(this).hasClass('active')) {
            if (!$(this).find('.tab-dot').length) {
                $(this).append('<span class="tab-dot"></span>');
            }
        } else {
            $(this).find('.tab-dot').remove();
        }
    });
}

// ==========================================================================
// Service & Image Management
// ==========================================================================

/**
 * Open service selector modal
 */
function openServiceSelector() {
    $('#service_selector_modal').modal('show');
}

/**
 * Open image upload modal
 */
function openImageSelector() {
    if (!$('#patient_id').val()) {
        toastr.warning(MedicalRecordConfig.translations.searchAndSelectPatient);
        return;
    }
    $('#image_upload_modal').modal('show');
}

/**
 * Remove a service from the list
 */
function removeService(serviceId) {
    var services = JSON.parse($('#treatment_services').val() || '[]');
    services = services.filter(function(s) { return s.id != serviceId; });
    $('#treatment_services').val(JSON.stringify(services));
    $('#treatment-service-tags .service-tag[data-id="' + serviceId + '"]').remove();
}

// ==========================================================================
// Template Insertion
// ==========================================================================

/**
 * Insert template content into a field
 */
function insertTemplate(field, type) {
    var templates = {
        'cleaning': '患者来院要求洁牙，无明显不适',
        'extraction': '患者要求拔除牙齿，该牙',
        'filling': '患者主诉牙齿有洞，进食嵌塞'
    };
    var template = templates[type] || '';
    var $field = $('#' + field);
    $field.val($field.val() + template);
    $field.focus();
}

// ==========================================================================
// History Records
// ==========================================================================

/**
 * Toggle history item expansion
 */
function toggleHistoryItem(element) {
    var $item = $(element).closest('.history-item');
    $item.toggleClass('expanded');
    $(element).text($item.hasClass('expanded') ?
        MedicalRecordConfig.translations.collapse :
        MedicalRecordConfig.translations.expand);
}

// ==========================================================================
// Quality Control
// ==========================================================================

/**
 * Run quality control check
 */
function runQualityCheck() {
    var errors = [];
    var warnings = [];

    // Check chief complaint
    var chiefComplaint = $('#chief_complaint').val();
    if (!chiefComplaint || chiefComplaint.length < 10) {
        errors.push({
            text: MedicalRecordConfig.translations.qcChiefComplaint,
            rule: MedicalRecordConfig.translations.qcChiefComplaintRule
        });
    }

    // Check teeth
    var relatedTeeth = JSON.parse($('#related_teeth').val() || '[]');
    var examTeeth = JSON.parse($('#examination_teeth').val() || '[]');
    if (relatedTeeth.length === 0 && examTeeth.length === 0) {
        warnings.push({
            text: MedicalRecordConfig.translations.qcTeethClarity,
            rule: MedicalRecordConfig.translations.qcTeethRule
        });
    }

    // Check treatment services
    var services = JSON.parse($('#treatment_services').val() || '[]');
    if (services.length === 0) {
        warnings.push({
            text: MedicalRecordConfig.translations.qcTreatmentLink,
            rule: MedicalRecordConfig.translations.qcTreatmentRule
        });
    }

    // Display QC results
    if (errors.length > 0 || warnings.length > 0) {
        var html = '';
        errors.forEach(function(e) {
            html += '<div class="qc-item error"><i class="fa fa-times-circle"></i> ' + e.text + ': ' + e.rule + '</div>';
        });
        warnings.forEach(function(w) {
            html += '<div class="qc-item warning"><i class="fa fa-exclamation-circle"></i> ' + w.text + ': ' + w.rule + '</div>';
        });
        $('#qc-items').html(html);
        $('#qc-panel').addClass('show');
        return errors.length === 0;
    }

    $('#qc-panel').removeClass('show');
    return true;
}

// ==========================================================================
// Save Medical Record
// ==========================================================================

/**
 * Save medical record (draft or submit)
 */
function saveMedicalRecord(action) {
    // If submitting, run quality check first
    if (action === 'submit') {
        if (!runQualityCheck()) {
            toastr.error(MedicalRecordConfig.translations.chiefComplaintRequired);
            return;
        }
    }

    // Basic validation
    var errors = [];
    if (!$('#chief_complaint').val()) errors.push(MedicalRecordConfig.translations.chiefComplaintRequired);
    if (!$('#examination').val()) errors.push(MedicalRecordConfig.translations.examinationRequired);
    if (!$('#diagnosis').val()) errors.push(MedicalRecordConfig.translations.diagnosisRequired);
    if (!$('#treatment').val()) errors.push(MedicalRecordConfig.translations.treatmentRequired);

    if (action === 'submit' && errors.length > 0) {
        var errorHtml = '';
        errors.forEach(function(e) { errorHtml += '<li>' + e + '</li>'; });
        $('#form-errors').show().find('ul').html(errorHtml);
        return;
    }

    $.LoadingOverlay("show");
    $('#btn-save-draft, #btn-submit-record').attr('disabled', true);

    var formData = $('#medical-record-form').serialize();
    formData += '&is_draft=' + (action === 'draft' ? '1' : '0');

    var caseId = $('#case_id').val();
    var url = caseId ? '/medical-cases/' + caseId : '/medical-cases';
    var method = caseId ? 'PUT' : 'POST';

    $.ajax({
        type: method,
        url: url,
        data: formData,
        success: function(response) {
            $.LoadingOverlay("hide");
            $('#btn-save-draft, #btn-submit-record').attr('disabled', false);

            if (response.status) {
                if (action === 'draft') {
                    toastr.success(MedicalRecordConfig.translations.draftSaved);
                    if (!caseId && response.id) {
                        $('#case_id').val(response.id);
                    }
                } else {
                    toastr.success(MedicalRecordConfig.translations.recordSubmitted);
                    if (response.id) {
                        window.location.href = '/medical-cases/' + response.id;
                    }
                }
            } else {
                toastr.error(response.message || MedicalRecordConfig.translations.errorOccurred);
            }
        },
        error: function(xhr) {
            $.LoadingOverlay("hide");
            $('#btn-save-draft, #btn-submit-record').attr('disabled', false);
            var json = xhr.responseJSON;
            if (json && json.errors) {
                var errorHtml = '';
                $.each(json.errors, function(key, value) {
                    errorHtml += '<li>' + value + '</li>';
                });
                $('#form-errors').show().find('ul').html(errorHtml);
            } else {
                toastr.error(MedicalRecordConfig.translations.errorOccurred);
            }
        }
    });
}

// ==========================================================================
// Patient Selection Management
// ==========================================================================

/**
 * Enable form with selected patient data
 */
function enableFormWithPatient() {
    if (!selectedPatientData) {
        return;
    }

    // Update the patient_id hidden field
    $('#patient_id').val(selectedPatientData.id);

    // Update patient info display
    $('#patient-avatar').text(selectedPatientData.surname.charAt(0));
    $('#patient-name').text(LanguageManager.joinName(selectedPatientData.surname, selectedPatientData.othername));

    // Calculate age if DOB exists
    var metaText = selectedPatientData.gender === 'Male' ?
        MedicalRecordConfig.translations.male :
        MedicalRecordConfig.translations.female;
    if (selectedPatientData.dob) {
        var dob = new Date(selectedPatientData.dob);
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear();
        var m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        metaText += ' ' + age + MedicalRecordConfig.translations.yearsOld;
    }
    $('#patient-meta').text(metaText);

    // Update allergy warning
    if (selectedPatientData.allergies) {
        $('#patient-allergy-warning').show().find('span').text(
            MedicalRecordConfig.translations.patientAllergy + '：' + selectedPatientData.allergies
        );
    } else {
        $('#patient-allergy-warning').hide();
    }

    // Update chronic diseases
    if (selectedPatientData.chronic) {
        $('#patient-chronic-info').show().find('span').text(selectedPatientData.chronic);
    } else {
        $('#patient-chronic-info').hide();
    }

    // Switch from selector to info display
    $('#patient-selector-section').hide();
    $('#selected-patient-info').show();

    // Enable the form
    $('#record-form-body').removeClass('disabled');
    $('#patient-select-prompt').hide();

    // Enable sidebar tool panels
    $('.sidebar-tool-panel').removeClass('disabled');

    // Enable buttons
    $('#btn-save-draft, #btn-submit-record').prop('disabled', false);

    // Focus on first field
    $('#chief_complaint').focus();
}

/**
 * Disable form when no patient selected
 */
function disableFormWithoutPatient() {
    // Clear patient_id
    $('#patient_id').val('');

    // Switch back to selector
    $('#selected-patient-info').hide();
    $('#patient-selector-section').show();

    // Disable the form
    $('#record-form-body').addClass('disabled');
    $('#patient-select-prompt').show();

    // Disable sidebar tool panels
    $('.sidebar-tool-panel').addClass('disabled');

    // Disable buttons
    $('#btn-save-draft, #btn-submit-record').prop('disabled', true);
}

/**
 * Change patient (switch back to selector)
 */
function changePatient() {
    selectedPatientData = null;
    $('#patient_selector').val('').trigger('change.select2');
    disableFormWithoutPatient();
}

// ==========================================================================
// Template Picker Integration
// ==========================================================================

/**
 * Initialize template picker for SOAP textareas
 * Uses existing TemplatePicker and QuickPhrasePicker from template_picker.js
 * Each field type matches database template types
 */
function initTemplatePicker() {
    // Chief complaint field - uses chief_complaint templates
    $('#chief_complaint').addClass('template-enabled phrase-enabled')
        .attr('data-template-type', 'chief_complaint');

    // History of present illness - only quick phrases, no templates
    $('#history_of_present_illness').addClass('phrase-enabled');

    // Examination field - uses progress_note templates (for SOAP format)
    $('#examination').addClass('template-enabled phrase-enabled')
        .attr('data-template-type', 'progress_note');

    // Auxiliary examination - only quick phrases, no templates (results are patient-specific)
    $('#auxiliary_examination').addClass('phrase-enabled');

    // Diagnosis field - uses diagnosis templates
    $('#diagnosis').addClass('template-enabled phrase-enabled')
        .attr('data-template-type', 'diagnosis');

    // Treatment fields - uses treatment_plan templates
    $('#treatment, #medical_orders').addClass('template-enabled phrase-enabled')
        .attr('data-template-type', 'treatment_plan');

    // Initialize TemplatePicker with custom insert handler
    if (typeof TemplatePicker !== 'undefined') {
        TemplatePicker.init({
            baseUrl: '',
            onInsert: handleTemplateInsert
        });
    }

    // Initialize QuickPhrasePicker if available
    if (typeof QuickPhrasePicker !== 'undefined') {
        QuickPhrasePicker.init({
            baseUrl: ''
        });
    }

    // Initialize PhrasePicker (semicolon-triggered dropdown) if available
    if (typeof PhrasePicker !== 'undefined') {
        PhrasePicker.init({
            baseUrl: ''
        });
    }
}

/**
 * Custom template insert handler for medical case form
 * Handles SOAP templates by filling multiple fields
 * Also replaces __ placeholder with selected teeth
 */
function handleTemplateInsert(template, $input) {
    var content = template.content;
    var parsed = null;

    // Try to parse as JSON (SOAP format)
    try {
        parsed = JSON.parse(content);
    } catch (e) {
        parsed = null;
    }

    // If it's a SOAP template (JSON with subjective/objective/assessment/plan)
    if (parsed && typeof parsed === 'object' && (parsed.subjective || parsed.objective || parsed.assessment || parsed.plan)) {
        var examTeeth = getTeethStringByField('#examination_teeth');
        var diagTeeth = getTeethStringByField('#related_teeth');

        // Fill each field with corresponding content (use field-appropriate teeth)
        if (parsed.subjective) {
            $('#chief_complaint').val(replaceToothPlaceholder(parsed.subjective, examTeeth));
        }
        if (parsed.objective) {
            $('#examination').val(replaceToothPlaceholder(parsed.objective, examTeeth));
        }
        if (parsed.assessment) {
            $('#diagnosis').val(replaceToothPlaceholder(parsed.assessment, diagTeeth));
        }
        if (parsed.plan) {
            $('#treatment').val(replaceToothPlaceholder(parsed.plan, diagTeeth));
        }

        // Update character counter
        $('#chief_complaint_count').text($('#chief_complaint').val().length);

        // Show success message
        if (typeof toastr !== 'undefined') {
            toastr.success(template.name + ' - 已填充到各字段');
        }

        return true; // Handled, prevent default insertion
    }

    // For plain text templates, determine teeth by target field context
    var inputId = $input.attr('id');
    var contextTeeth = (inputId === 'diagnosis' || inputId === 'treatment')
        ? getTeethStringByField('#related_teeth')
        : getTeethStringByField('#examination_teeth');
    content = replaceToothPlaceholder(content, contextTeeth);

    // Insert into current field
    var val = $input.val();
    var cursorPos = $input[0].selectionStart;
    var newVal = val.substring(0, cursorPos) + content + val.substring(cursorPos);
    $input.val(newVal);

    // Set cursor position after inserted content
    var newCursorPos = cursorPos + content.length;
    $input[0].setSelectionRange(newCursorPos, newCursorPos);
    $input.focus();

    return true; // Handled
}

/**
 * Get teeth string for a specific field (e.g., "47" or "46,47")
 * @param {string} inputId - jQuery selector for hidden input (default: examination)
 */
function getTeethStringByField(inputId) {
    var teeth = JSON.parse($(inputId).val() || '[]');
    if (teeth.length === 0) {
        return '__'; // Keep placeholder if no teeth selected
    }
    return teeth.join(',');
}

/**
 * Get selected teeth as a string - uses examination teeth as default
 */
function getSelectedTeethString() {
    return getTeethStringByField('#examination_teeth');
}

/**
 * Replace __ placeholder with tooth numbers
 */
function replaceToothPlaceholder(text, teethStr) {
    if (!text) return text;
    return text.replace(/__/g, teethStr);
}
