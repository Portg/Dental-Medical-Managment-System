/**
 * FDI Dental Chart Editor
 * =======================
 * Flow: select teeth first → apply a status to the selection.
 *
 * Depends: jQuery, LanguageManager, csrf meta, #global_appointment_id
 */
(function (window, $) {
    'use strict';

    var STATUS_MAP = {
        caries:   { color: '2',  bg: '#EAB308', shortKey: 'short_caries' },
        filled:   { color: '1',  bg: '#EF4444', shortKey: 'short_filled' },
        rct:      { color: '3',  bg: '#F97316', shortKey: 'short_rct' },
        crown:    { color: '8',  bg: '#2563EB', shortKey: 'short_crown' },
        missing:  { color: '4',  bg: '#F43F5E', shortKey: 'short_missing' },
        implant:  { color: '6',  bg: '#A855F7', shortKey: 'short_implant' },
        impacted: { color: '11', bg: '#14B8A6', shortKey: 'short_impacted' }
    };

    var COLOR_TO_STATUS = {
        '1': 'filled',
        '2': 'caries',
        '3': 'rct',
        '4': 'missing',
        '6': 'implant',
        '8': 'crown',
        '11': 'impacted'
    };

    var STATUS_PRIORITY = ['missing', 'implant', 'impacted', 'crown', 'rct', 'filled', 'caries'];

    var marks = {};          // toothNumber -> statusKey
    var selectedTeeth = {};  // toothNumber -> true

    function t(key, fallback) {
        if (typeof LanguageManager !== 'undefined' && LanguageManager.trans) {
            var v = LanguageManager.trans('odontogram.' + key);
            if (v && v !== 'odontogram.' + key) return v;
        }
        return fallback || key;
    }

    function shortLabel(status) {
        var meta = STATUS_MAP[status];
        if (!meta) return '';
        return t(meta.shortKey, t(status === 'filled' ? 'filling' : status, status));
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function appointmentId() {
        return ($('#global_appointment_id').val() || '').trim();
    }

    function selectedCount() {
        return Object.keys(selectedTeeth).length;
    }

    function applyToothStyle($el, status) {
        var tooth = String($el.data('tooth'));
        var isSelected = !!selectedTeeth[tooth];
        $el.toggleClass('selected', isSelected);
        $el.removeClass('marked');
        $el.css({ background: '', borderColor: '', color: '' });
        $el.find('.dce-mark').text('');

        if (status && STATUS_MAP[status]) {
            var meta = STATUS_MAP[status];
            $el.addClass('marked').css({
                background: meta.bg,
                borderColor: meta.bg
            });
            $el.find('.dce-mark').text(shortLabel(status));
        }
    }

    function refreshTooth($el) {
        var tooth = String($el.data('tooth'));
        applyToothStyle($el, marks[tooth] || null);
    }

    function refreshAllTeeth() {
        $('.dce-tooth').each(function () {
            refreshTooth($(this));
        });
    }

    function refreshSelectionChip() {
        var n = selectedCount();
        var $chip = $('#dce-selection-chip');
        var $label = $('#dce-selection-label');
        if (!n) {
            $chip.addClass('is-empty');
            $label.text(t('no_teeth_selected', '未选牙位'));
        } else {
            $chip.removeClass('is-empty');
            var nums = Object.keys(selectedTeeth).sort(function (a, b) { return Number(a) - Number(b); });
            $label.text(t('selected_teeth', '已选') + ' ' + nums.join('、'));
        }
        $('#dce-status-step').toggleClass('dce-ready', n > 0);
    }

    function refreshSummary() {
        var parts = [];
        Object.keys(marks).sort(function (a, b) { return Number(a) - Number(b); }).forEach(function (tooth) {
            parts.push(tooth + ' ' + shortLabel(marks[tooth]));
        });
        var $sum = $('#dce-summary');
        if (!parts.length) {
            $sum.html(t('no_marks_yet', '尚未标记牙位'));
        } else {
            $sum.html('<strong>' + t('marked_teeth', '已标记') + '：</strong>' + parts.join('、'));
        }
    }

    function onToothClick() {
        var tooth = String($(this).data('tooth'));
        if (selectedTeeth[tooth]) {
            delete selectedTeeth[tooth];
        } else {
            selectedTeeth[tooth] = true;
        }
        refreshTooth($(this));
        refreshSelectionChip();
    }

    function applyStatusToSelection(status) {
        var teeth = Object.keys(selectedTeeth);
        if (!teeth.length) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(t('select_teeth_first', '请先选择牙位'));
            }
            return;
        }

        teeth.forEach(function (tooth) {
            if (status === 'clear') {
                delete marks[tooth];
            } else {
                marks[tooth] = status;
            }
        });

        // Keep selection so user can re-apply another status if needed
        refreshAllTeeth();
        refreshSummary();
        refreshSelectionChip();

        // Brief flash on status button
        var $btn = $('.dce-status-btn[data-status="' + status + '"]');
        $btn.addClass('just-applied');
        setTimeout(function () { $btn.removeClass('just-applied'); }, 350);
    }

    function pickPrimaryStatus(rows) {
        var statuses = [];
        rows.forEach(function (row) {
            var st = row.tooth_status || COLOR_TO_STATUS[String(row.color)];
            if (st && STATUS_MAP[st] && statuses.indexOf(st) === -1) {
                statuses.push(st);
            }
        });
        for (var i = 0; i < STATUS_PRIORITY.length; i++) {
            if (statuses.indexOf(STATUS_PRIORITY[i]) !== -1) {
                return STATUS_PRIORITY[i];
            }
        }
        return statuses[0] || null;
    }

    function loadChart() {
        var id = appointmentId();
        if (!id) return;
        $.getJSON('/dental-charting/' + id, function (rows) {
            marks = {};
            selectedTeeth = {};
            var byTooth = {};
            (rows || []).forEach(function (row) {
                var tooth = String(row.tooth_number || row.tooth || '');
                if (!tooth) return;
                if (!byTooth[tooth]) byTooth[tooth] = [];
                byTooth[tooth].push(row);
            });
            Object.keys(byTooth).forEach(function (tooth) {
                var status = pickPrimaryStatus(byTooth[tooth]);
                if (status) marks[tooth] = status;
            });
            refreshAllTeeth();
            refreshSummary();
            refreshSelectionChip();
        }).fail(function () {
            if (typeof toastr !== 'undefined') {
                toastr.error(t('load_failed', '加载牙位图失败'));
            }
        });
    }

    function buildPayload() {
        var data = [];
        Object.keys(marks).forEach(function (tooth) {
            var status = marks[tooth];
            var meta = STATUS_MAP[status];
            if (!meta) return;
            var n = Number(tooth);
            data.push({
                tooth: n,
                tooth_number: n,
                tooth_type: n >= 51 ? 'primary' : 'permanent',
                tooth_status: status,
                section: null,
                position: null,
                color: meta.color
            });
        });
        return data;
    }

    function saveChart() {
        var id = appointmentId();
        if (!id) return;
        var data = buildPayload();
        var confirmMsg = data.length
            ? t('confirm_save_chart', '确认保存牙位图标记？')
            : t('confirm_clear_chart', '当前无标记，确认清空该患者牙位图？');
        var doSave = function () {
            $.ajax({
                type: 'POST',
                url: '/dental-charting',
                data: {
                    _token: csrfToken(),
                    appointment_id: id,
                    data: data
                },
                success: function (res) {
                    var msg = (res && res.message) || t('chart_saved_success', '保存成功');
                    if (typeof toastr !== 'undefined') toastr.success(msg);
                    else if (typeof swal === 'function') swal('OK', msg, 'success');
                    else alert(msg);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || t('save_failed', '保存失败');
                    if (typeof toastr !== 'undefined') toastr.error(msg);
                    else alert(msg);
                }
            });
        };

        if (typeof swal === 'function') {
            swal({
                title: confirmMsg,
                type: 'warning',
                showCancelButton: true,
                confirmButtonClass: 'btn green-meadow',
                confirmButtonText: t('save_changes', '保存更改'),
                closeOnConfirm: true
            }, doSave);
        } else if (window.confirm(confirmMsg)) {
            doSave();
        }
    }

    function bind() {
        $(document).on('click', '.dce-status-btn', function () {
            applyStatusToSelection($(this).data('status'));
        });
        $(document).on('click', '.dce-tab', function () {
            var target = $(this).data('target');
            $('.dce-tab').removeClass('active');
            $(this).addClass('active');
            $('.dce-panel').removeClass('active');
            $('#' + target).addClass('active');
        });
        $(document).on('click', '.dce-tooth', onToothClick);
        $(document).on('click', '#dce-save-btn', saveChart);
    }

    window.initDentalChartEditor = function () {
        if (!$('#dce-editor').length) return;
        bind();
        refreshSummary();
        refreshSelectionChip();
        loadChart();
    };

    $(function () {
        if ($('#dce-editor').length) {
            window.initDentalChartEditor();
        }
    });

})(window, jQuery);
