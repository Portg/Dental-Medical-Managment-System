/**
 * Work Log OCR Recognize Page - Frontend Logic
 *
 * Phase 1: upload a work-log table photo.
 * Phase 2: review/correct an editable multi-row grid, then batch-save.
 */
(function () {
    'use strict';

    var selectedFile = null;
    var sourceImage = null;

    var FIELDS = [
        'log_date', 'patient_name', 'gender', 'age', 'visit_type',
        'phone', 'tooth_position', 'diagnosis', 'prescription',
        'doctor_name_raw', 'amount'
    ];

    $(document).ready(function () {
        initDropZone();
        $('#btn-recognize').on('click', uploadAndRecognize);
        $('#btn-back-upload').on('click', backToUpload);
        $('#btn-add-row').on('click', function () {
            addGridRow({});
        });
        $('#btn-save').on('click', submitBatch);
    });

    // ==================== Drop Zone ====================

    function initDropZone() {
        var dropZone = $('#drop-zone');
        var fileInput = $('#image-input');

        dropZone.on('click', function () {
            if (!selectedFile) {
                fileInput.trigger('click');
            }
        });
        fileInput.on('click', function (e) {
            e.stopPropagation();
        });
        fileInput.on('change', function () {
            if (this.files && this.files[0]) {
                handleFileSelected(this.files[0]);
            }
        });
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
        $('#btn-clear-image').on('click', function (e) {
            e.stopPropagation();
            clearSelectedFile();
        });
    }

    function handleFileSelected(file) {
        var validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/bmp'];
        if (validTypes.indexOf(file.type) === -1) {
            toastr.error(LanguageManager.trans('work_log.supported_formats'));
            return;
        }
        if (file.size > 8 * 1024 * 1024) {
            toastr.error(LanguageManager.trans('work_log.file_too_large'));
            return;
        }
        selectedFile = file;
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#preview-img-upload').attr('src', e.target.result);
            $('.drop-zone-content').hide();
            $('#image-preview-upload').show();
        };
        reader.readAsDataURL(file);
        $('#btn-recognize').prop('disabled', false);
    }

    function clearSelectedFile() {
        selectedFile = null;
        $('#image-input').val('');
        $('#preview-img-upload').attr('src', '');
        $('#image-preview-upload').hide();
        $('.drop-zone-content').show();
        $('#btn-recognize').prop('disabled', true);
    }

    // ==================== Recognition ====================

    function uploadAndRecognize() {
        if (!selectedFile) return;

        var formData = new FormData();
        formData.append('image', selectedFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

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
                    toastr.error(response.message || LanguageManager.trans('work_log.ocr_error'));
                }
            },
            error: function () {
                $('#loading-overlay').removeClass('active');
                toastr.error(LanguageManager.trans('work_log.ocr_error'));
            }
        });
    }

    function showResults(data) {
        $('#upload-section').hide();
        $('#result-section').show();

        sourceImage = data.source_image || null;

        var reader = new FileReader();
        reader.onload = function (e) {
            $('#result-image').attr('src', e.target.result);
        };
        reader.readAsDataURL(selectedFile);

        showConfidence(data.confidence);

        $('#work-log-grid-body').empty();

        if (!data.header_found) {
            $('#no-header-warning').show();
            for (var k = 0; k < 5; k++) {
                addGridRow({});
            }
            return;
        }

        $('#no-header-warning').hide();
        var rows = data.rows || [];
        if (rows.length === 0) {
            addGridRow({});
        } else {
            rows.forEach(function (row) {
                addGridRow(row);
            });
        }
    }

    function showConfidence(confidence) {
        var pct = Math.round((confidence || 0) * 100);
        var alert = $('#confidence-alert');
        alert.removeClass('alert-success alert-warning alert-danger');
        if (pct >= 75) {
            alert.addClass('alert-success');
        } else if (pct >= 50) {
            alert.addClass('alert-warning');
        } else {
            alert.addClass('alert-danger');
        }
        $('#confidence-text').text(LanguageManager.trans('work_log.confidence'));
        $('#confidence-value').text(pct + '%');
    }

    // ==================== Editable Grid ====================

    function addGridRow(row) {
        var tbody = $('#work-log-grid-body');
        var idx = tbody.children('tr').length + 1;

        var tr = $('<tr></tr>');
        tr.append('<td class="row-no">' + idx + '</td>');

        FIELDS.forEach(function (field) {
            var val = row[field] != null ? String(row[field]) : '';
            var td = $('<td></td>');
            if (field === 'visit_type') {
                var sel = $('<select class="form-control form-control-sm cell-input" data-field="visit_type"></select>');
                sel.append('<option value=""></option>');
                sel.append('<option value="initial">' + LanguageManager.trans('work_log.visit_initial') + '</option>');
                sel.append('<option value="revisit">' + LanguageManager.trans('work_log.visit_revisit') + '</option>');
                if (/初/.test(val)) {
                    sel.val('initial');
                } else if (/复/.test(val)) {
                    sel.val('revisit');
                } else if (val === 'initial' || val === 'revisit') {
                    sel.val(val);
                }
                td.append(sel);
            } else {
                var input = $('<input type="text" class="form-control form-control-sm cell-input">');
                input.attr('data-field', field);
                input.val(val);
                td.append(input);
            }
            tr.append(td);
        });

        var delTd = $('<td></td>');
        var delBtn = $('<button type="button" class="btn btn-sm btn-outline-danger btn-del-row"><i class="fa fa-trash"></i></button>');
        delBtn.on('click', function () {
            tr.remove();
            renumberRows();
        });
        delTd.append(delBtn);
        tr.append(delTd);

        tbody.append(tr);
    }

    function renumberRows() {
        $('#work-log-grid-body tr').each(function (i) {
            $(this).find('.row-no').text(i + 1);
        });
    }

    function collectRows() {
        var rows = [];
        $('#work-log-grid-body tr').each(function () {
            var rowObj = {};
            var hasValue = false;
            $(this).find('.cell-input').each(function () {
                var field = $(this).data('field');
                var v = $.trim($(this).val());
                rowObj[field] = v;
                if (v !== '') {
                    hasValue = true;
                }
            });
            if (hasValue) {
                rows.push(rowObj);
            }
        });
        return rows;
    }

    // ==================== Save ====================

    function submitBatch() {
        var rows = collectRows();
        if (rows.length === 0) {
            toastr.warning(LanguageManager.trans('work_log.no_rows'));
            return;
        }

        var payload = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            rows: rows,
            link_patients: $('#opt-link-patients').is(':checked') ? 1 : 0,
            generate_invoices: $('#opt-generate-invoices').is(':checked') ? 1 : 0,
            year: $('#opt-year').val(),
            source_image: sourceImage
        };

        $('#btn-save').prop('disabled', true);
        $.LoadingOverlay && $.LoadingOverlay('show');

        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: payload,
            success: function (response) {
                $.LoadingOverlay && $.LoadingOverlay('hide');
                $('#btn-save').prop('disabled', false);
                if (response.status === 1) {
                    toastr.success(response.message);
                    setTimeout(function () {
                        window.location.href = workLogsUrl;
                    }, 1200);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function () {
                $.LoadingOverlay && $.LoadingOverlay('hide');
                $('#btn-save').prop('disabled', false);
                toastr.error(LanguageManager.trans('work_log.save_failed'));
            }
        });
    }

    function backToUpload() {
        $('#result-section').hide();
        $('#upload-section').show();
        $('#work-log-grid-body').empty();
        clearSelectedFile();
    }
})();
