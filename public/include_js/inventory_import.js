/**
 * Inventory Bulk Import – Client-side Logic
 * Handles: drag-and-drop upload, AJAX import, result rendering.
 */
(function ($) {
    'use strict';

    var selectedFile = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    var $dropZone     = $('#drop-zone');
    var $fileInput    = $('#import-file');
    var $fileInfo     = $('#file-info');
    var $fileName     = $('#file-name');
    var $btnClear     = $('#btn-clear-file');
    var $btnImport    = $('#btn-import');
    var $btnReset     = $('#btn-reset');
    var $progress     = $('#import-progress');
    var $result       = $('#import-result');
    var $summary      = $('#result-summary');
    var $errorSection = $('#error-section');
    var $errorList    = $('#error-list');

    // ── File selection ────────────────────────────────────────────────────────

    $fileInput.on('change', function () {
        if (this.files && this.files.length > 0) {
            handleFile(this.files[0]);
        }
    });

    $dropZone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.addClass('dragover');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.removeClass('dragover');
    }).on('drop', function (e) {
        var dt = e.originalEvent.dataTransfer;
        if (dt && dt.files.length > 0) {
            handleFile(dt.files[0]);
        }
    });

    $btnClear.on('click', function () {
        resetUpload();
    });

    $btnReset.on('click', function () {
        resetUpload();
        $result.addClass('d-none');
    });

    // ── Import button ─────────────────────────────────────────────────────────

    $btnImport.on('click', function () {
        if (!selectedFile) return;
        doImport();
    });

    // ── Functions ─────────────────────────────────────────────────────────────

    function handleFile(file) {
        // Validate extension
        var ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].indexOf(ext) === -1) {
            showToast(LanguageManager.trans('inventory.import_file_type'), 'error');
            return;
        }
        // Validate size (10 MB)
        if (file.size > 10 * 1024 * 1024) {
            showToast(LanguageManager.trans('inventory.file_too_large'), 'error');
            return;
        }

        selectedFile = file;
        $fileName.text(file.name);
        $fileInfo.removeClass('d-none');
        $dropZone.addClass('d-none');
        $btnImport.prop('disabled', false);
        $result.addClass('d-none');
    }

    function resetUpload() {
        selectedFile = null;
        $fileInput.val('');
        $fileInfo.addClass('d-none');
        $dropZone.removeClass('d-none');
        $btnImport.prop('disabled', true);
        $btnReset.hide();
        $progress.addClass('d-none');
    }

    function doImport() {
        var formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', csrfToken);

        $btnImport.prop('disabled', true);
        $progress.removeClass('d-none');
        $result.addClass('d-none');

        $.ajax({
            url: importUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $progress.addClass('d-none');
                $btnReset.show();

                if (res.status) {
                    renderResult(res);
                } else {
                    showToast(res.message || LanguageManager.trans('inventory.import_failed'), 'error');
                    $btnImport.prop('disabled', false);
                }
            },
            error: function (xhr) {
                $progress.addClass('d-none');
                $btnImport.prop('disabled', false);

                var msg = LanguageManager.trans('inventory.import_failed');
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.file) {
                    msg = xhr.responseJSON.errors.file[0];
                }
                showToast(msg, 'error');
            },
        });
    }

    function renderResult(res) {
        var imported = res.imported || 0;
        var skipped  = res.skipped  || 0;
        var errors   = res.errors   || [];
        var hasErrors = errors.length > 0;

        // Summary
        var summaryHtml =
            '<span class="result-success-count">' +
                LanguageManager.trans('inventory.import_success_count', { count: imported }) +
            '</span>' +
            '&nbsp;&nbsp;' +
            '<span class="result-skip-count">' +
                LanguageManager.trans('inventory.import_skip_count', { count: skipped }) +
            '</span>';

        $summary.html(summaryHtml);
        $summary.toggleClass('has-errors', hasErrors);

        // Error list
        if (hasErrors) {
            var rows = errors.map(function (err) {
                return '<tr>' +
                    '<td>' + LanguageManager.trans('inventory.import_row', { row: err.row }) + '</td>' +
                    '<td>' + escapeHtml(err.reason) + '</td>' +
                    '</tr>';
            }).join('');
            $errorList.html(rows);
            $errorSection.removeClass('d-none');
        } else {
            $errorSection.addClass('d-none');
        }

        $result.removeClass('d-none');

        if (imported > 0) {
            showToast(res.message, 'success');
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showToast(msg, type) {
        type = type || 'info';
        if (typeof toastr !== 'undefined') {
            toastr[type](msg);
        } else {
            alert(msg);
        }
    }

})(jQuery);
