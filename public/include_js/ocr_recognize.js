/**
 * OCR Recognize Page - Frontend Logic
 */
(function () {
    'use strict';

    var selectedFile = null;

    // ==================== Initialization ====================

    $(document).ready(function () {
        initEventHandlers();
        initDropZone();
        initPatientModeToggle();
        initPatientSearch();

        // Set default case date to today
        $('#field-case_date').val(new Date().toISOString().slice(0, 10));
    });

    // ==================== Drop Zone ====================

    function initDropZone() {
        var dropZone = $('#drop-zone');
        var fileInput = $('#image-input');

        // Click to open file picker
        dropZone.on('click', function () {
            if (!selectedFile) {
                fileInput.trigger('click');
            }
        });

        // Prevent file input click from bubbling back to drop zone (infinite loop)
        fileInput.on('click', function (e) {
            e.stopPropagation();
        });

        // File selected
        fileInput.on('change', function () {
            if (this.files && this.files[0]) {
                handleFileSelected(this.files[0]);
            }
        });

        // Drag & drop
        dropZone.on('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        dropZone.on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        dropZone.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');

            var files = e.originalEvent.dataTransfer.files;
            if (files && files[0]) {
                handleFileSelected(files[0]);
            }
        });

        // Clear image button
        $('#btn-clear-image').on('click', function (e) {
            e.stopPropagation();
            clearSelectedFile();
        });
    }

    function handleFileSelected(file) {
        // Validate file type
        var validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/bmp'];
        if (validTypes.indexOf(file.type) === -1) {
            toastr.error(LanguageManager.trans('ocr.supported_formats'));
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            toastr.error(LanguageManager.trans('ocr.file_too_large'));
            return;
        }

        selectedFile = file;

        // Show preview
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#preview-img-upload').attr('src', e.target.result);
            $('.drop-zone-content').addClass('d-none');
            $('#image-preview-upload').removeClass('d-none');
        };
        reader.readAsDataURL(file);

        $('#btn-recognize').prop('disabled', false);
    }

    function clearSelectedFile() {
        selectedFile = null;
        $('#image-input').val('');
        $('#preview-img-upload').attr('src', '');
        $('#image-preview-upload').addClass('d-none');
        $('.drop-zone-content').removeClass('d-none');
        $('#btn-recognize').prop('disabled', true);
    }

    // ==================== OCR Recognition ====================

    function initEventHandlers() {
        $('#btn-recognize').on('click', uploadAndRecognize);
        $('#btn-create').on('click', submitCreateFromOcr);
        $('#btn-toggle-raw').on('click', toggleRawText);
        $('#btn-back-upload').on('click', backToUpload);
    }

    function uploadAndRecognize() {
        if (!selectedFile) return;

        var formData = new FormData();
        formData.append('image', selectedFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        // Show loading
        $('#loading-overlay').addClass('active');

        $.ajax({
            url: recognizeUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $('#loading-overlay').removeClass('active');

                if (response.status === 1) {
                    showResults(response.data);
                } else {
                    toastr.error(response.message || LanguageManager.trans('ocr.ocr_error'));
                }
            },
            error: function (xhr) {
                $('#loading-overlay').removeClass('active');
                toastr.error(LanguageManager.trans('ocr.ocr_error'));
            }
        });
    }

    // ==================== Show Results ====================

    function showResults(data) {
        // Switch to results view
        $('#upload-section').addClass('d-none');
        $('#result-section').removeClass('d-none');

        // Init datepickers now that elements are visible
        initDatepickers();

        // Show original image
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#result-image').attr('src', e.target.result);
        };
        reader.readAsDataURL(selectedFile);

        // Show raw text
        $('#raw-text-content').text(data.raw_text || LanguageManager.trans('ocr.no_text_detected'));

        // Show confidence
        showConfidence(data.confidence);

        // Fill form fields
        fillFormFromOcr(data);
    }

    function fillFormFromOcr(data) {
        var patient = data.patient || {};
        var caseData = data.case || {};

        // Patient fields
        $('#field-full_name').val(patient.full_name || '');
        $('#field-gender').val(patient.gender || '');
        $('#field-age').val(patient.age || '');
        $('#field-phone_no').val(patient.phone_no || '');
        $('#field-dob').val(patient.dob || '');
        $('#field-address').val(patient.address || '');
        $('#field-nin').val(patient.nin || '');
        $('#field-blood_type').val(patient.blood_type || '');
        $('#field-drug_allergies_other').val(patient.drug_allergies_other || '');

        // Case fields
        $('#field-chief_complaint').val(caseData.chief_complaint || '');
        $('#field-history_of_present_illness').val(caseData.history_of_present_illness || '');
        $('#field-examination').val(caseData.examination || '');
        $('#field-auxiliary_examination').val(caseData.auxiliary_examination || '');
        $('#field-diagnosis').val(caseData.diagnosis || '');
        $('#field-treatment').val(caseData.treatment || '');
        $('#field-medical_orders').val(caseData.medical_orders || '');
    }

    function showConfidence(confidence) {
        var alert = $('#confidence-alert');
        var text = $('#confidence-text');
        var badge = $('#confidence-value');

        alert.removeClass('confidence-high confidence-medium confidence-low');
        var pct = Math.round(confidence * 100);
        badge.text(pct + '%');

        if (confidence >= 0.9) {
            alert.addClass('confidence-high');
            text.text(LanguageManager.trans('ocr.confidence_high'));
            badge.addClass('badge-success').removeClass('badge-warning badge-danger');
        } else if (confidence >= 0.7) {
            alert.addClass('confidence-medium');
            text.text(LanguageManager.trans('ocr.confidence_medium'));
            badge.addClass('badge-warning').removeClass('badge-success badge-danger');
        } else {
            alert.addClass('confidence-low');
            text.text(LanguageManager.trans('ocr.confidence_low'));
            badge.addClass('badge-danger').removeClass('badge-success badge-warning');
        }
    }

    // ==================== Patient Mode Toggle ====================

    function initPatientModeToggle() {
        $('input[name="patient_mode"]').on('change', function () {
            togglePatientMode($(this).val());
        });
    }

    function togglePatientMode(mode) {
        if (mode === 'existing') {
            $('#new-patient-fields').addClass('d-none');
            $('#existing-patient-box').removeClass('d-none');
            $('#btn-create-text').text(LanguageManager.trans('ocr.create_case_only'));
        } else {
            $('#new-patient-fields').removeClass('d-none');
            $('#existing-patient-box').addClass('d-none');
            $('#btn-create-text').text(LanguageManager.trans('ocr.create_patient_and_case'));
        }
    }

    // ==================== Patient Search (Select2) ====================

    function initPatientSearch() {
        $('#patient-search').select2({
            ajax: {
                url: searchPatientUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            },
            minimumInputLength: 1,
            placeholder: LanguageManager.trans('ocr.search_patient_placeholder'),
            allowClear: true
        });
    }

    // ==================== Datepickers ====================

    var datepickersInitialized = false;

    function initDatepickers() {
        if (datepickersInitialized) return;
        datepickersInitialized = true;

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            language: document.documentElement.lang || 'zh-CN'
        });
    }

    // ==================== Create Patient + Case ====================

    function submitCreateFromOcr() {
        var btn = $('#btn-create');
        btn.prop('disabled', true);

        var formData = $('#ocr-create-form').serialize();

        $.ajax({
            url: createUrl,
            type: 'POST',
            data: formData,
            success: function (response) {
                btn.prop('disabled', false);

                if (response.status === 1) {
                    toastr.success(response.message || LanguageManager.trans('ocr.create_success'));

                    // Redirect to patient detail page after short delay
                    setTimeout(function () {
                        window.location.href = patientShowUrl + '/' + response.data.patient_id;
                    }, 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error(LanguageManager.trans('common.error'));
                }
            }
        });
    }

    // ==================== Utilities ====================

    function toggleRawText() {
        var body = $('#raw-text-body');
        var icon = $('#btn-toggle-raw i');

        body.toggleClass('d-none');
        icon.toggleClass('fa-chevron-down fa-chevron-up');
    }

    function backToUpload() {
        $('#result-section').addClass('d-none');
        $('#upload-section').removeClass('d-none');
    }

})();
