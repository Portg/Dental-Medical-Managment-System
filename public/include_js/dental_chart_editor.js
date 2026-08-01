/**
 * FDI Dental Chart Editor
 * =======================
 * Linked UI state:
 *   - selectedTeeth  → top chip + step-2 ready
 *   - marks          → tooth colors + bottom summary
 *   - eraseMode      → clear-tool affordance
 *
 * Select / deselect only changes selection.
 * Marks change only via status buttons, erase mode, or summary ×.
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
    var eraseMode = false;
    var bound = false;

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

    function selectedList() {
        return Object.keys(selectedTeeth).sort(function (a, b) { return Number(a) - Number(b); });
    }

    function markedList() {
        return Object.keys(marks).sort(function (a, b) { return Number(a) - Number(b); });
    }

    function setEraseMode(on) {
        eraseMode = !!on;
        $('.dce-status-btn.dce-clear').toggleClass('is-active', eraseMode);
        $('#dce-editor').toggleClass('dce-erase-mode', eraseMode);
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
                borderColor: isSelected ? '#1D4ED8' : meta.bg
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
        var $chip = $('#dce-selection-chip');
        var $label = $('#dce-selection-label');
        var selected = selectedList();

        if (eraseMode) {
            $chip.removeClass('is-empty').addClass('is-erase');
            $label.text(t('erase_mode_hint', '清除中：点击牙位取消标注'));
            $('#dce-status-step').addClass('dce-ready');
            return;
        }

        $chip.removeClass('is-erase');
        if (!selected.length) {
            $chip.addClass('is-empty');
            $label.text(t('no_teeth_selected', '未选牙位'));
        } else {
            $chip.removeClass('is-empty');
            $label.text(t('selected_teeth', '已选') + ' ' + selected.join('、'));
        }
        $('#dce-status-step').toggleClass('dce-ready', selected.length > 0);
    }

    function refreshSummary() {
        var $sum = $('#dce-summary');
        var teeth = markedList();
        var selected = selectedList();

        if (teeth.length) {
            var html = '<strong>' + t('marked_teeth', '已标记') + '：</strong>';
            teeth.forEach(function (tooth) {
                html += '<span class="dce-mark-chip" data-tooth="' + tooth + '" title="' +
                    t('unmark_tooth', '取消标记') + '">' +
                    tooth + ' ' + shortLabel(marks[tooth]) +
                    '<button type="button" class="dce-mark-remove" data-tooth="' + tooth +
                    '" aria-label="' + t('unmark_tooth', '取消标记') + '">&times;</button></span>';
            });
            $sum.html(html);
            return;
        }

        // No marks: distinguish idle vs "selected but not yet marked"
        if (selected.length) {
            $sum.html(
                t('selected_awaiting_status', '已选') + ' ' + selected.join('、') +
                ' — ' + t('apply_status_next', '请点下方状态完成标记')
            );
            return;
        }

        $sum.html(t('no_marks_yet', '尚未标记牙位'));
    }

    /** Single sync point so chip / summary / teeth / erase UI stay linked */
    function syncUI(options) {
        options = options || {};
        if (options.teeth !== false) refreshAllTeeth();
        refreshSummary();
        refreshSelectionChip();
    }

    function unmarkTooth(tooth) {
        tooth = String(tooth);
        if (!marks[tooth]) return;
        delete marks[tooth];
        syncUI();
    }

    function onToothClick() {
        var tooth = String($(this).data('tooth'));

        if (eraseMode) {
            if (marks[tooth]) {
                delete marks[tooth];
                syncUI();
            }
            return;
        }

        // Toggle selection only — never mutates marks
        if (selectedTeeth[tooth]) {
            delete selectedTeeth[tooth];
        } else {
            selectedTeeth[tooth] = true;
        }
        syncUI();
    }

    function flashStatusBtn(status) {
        var $btn = $('.dce-status-btn[data-status="' + status + '"]');
        $btn.addClass('just-applied');
        setTimeout(function () { $btn.removeClass('just-applied'); }, 350);
    }

    function applyStatusToSelection(status) {
        var teeth = selectedList();
        if (!teeth.length) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(t('select_teeth_first', '请先选择牙位'));
            }
            return;
        }

        teeth.forEach(function (tooth) {
            marks[tooth] = status;
        });

        // Finish this action: marks stay, selection clears → summary shows 已标记
        selectedTeeth = {};
        setEraseMode(false);
        syncUI();
        flashStatusBtn(status);
    }

    function onClearClick() {
        var teeth = selectedList();

        if (teeth.length) {
            // Clear marks on the current selection, then drop selection
            teeth.forEach(function (tooth) {
                delete marks[tooth];
            });
            selectedTeeth = {};
            setEraseMode(false);
            syncUI();
            flashStatusBtn('clear');
            return;
        }

        // No selection: toggle erase mode
        setEraseMode(!eraseMode);
        syncUI({ teeth: false });
        if (eraseMode) flashStatusBtn('clear');
    }

    function onStatusClick() {
        var status = $(this).data('status');
        if (status === 'clear') {
            onClearClick();
            return;
        }
        setEraseMode(false);
        applyStatusToSelection(status);
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
            setEraseMode(false);
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
            syncUI();
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
                contentType: 'application/json',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                data: JSON.stringify({
                    appointment_id: Number(id),
                    data: data
                }),
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
        if (bound) return;
        bound = true;
        $(document).on('click.dce', '.dce-status-btn', onStatusClick);
        $(document).on('click.dce', '.dce-tab', function () {
            var target = $(this).data('target');
            $('.dce-tab').removeClass('active');
            $(this).addClass('active');
            $('.dce-panel').removeClass('active');
            $('#' + target).addClass('active');
        });
        $(document).on('click.dce', '.dce-tooth', onToothClick);
        $(document).on('click.dce', '#dce-save-btn', saveChart);
        $(document).on('click.dce', '.dce-mark-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            unmarkTooth($(this).data('tooth'));
        });
    }

    window.initDentalChartEditor = function () {
        if (!$('#dce-editor').length) return;
        bind();
        syncUI();
        loadChart();
    };

    // Expose pure state helpers for interaction tests (no DOM required)
    window.__dceTestApi = {
        reset: function () {
            marks = {};
            selectedTeeth = {};
            eraseMode = false;
        },
        getState: function () {
            return {
                marks: Object.assign({}, marks),
                selected: selectedList(),
                eraseMode: eraseMode,
                summaryKind: markedList().length
                    ? 'marked'
                    : (selectedList().length ? 'awaiting' : 'empty')
            };
        },
        toggleTooth: function (tooth) {
            tooth = String(tooth);
            if (eraseMode) {
                delete marks[tooth];
                return;
            }
            if (selectedTeeth[tooth]) delete selectedTeeth[tooth];
            else selectedTeeth[tooth] = true;
        },
        applyStatus: function (status) {
            var teeth = selectedList();
            if (!teeth.length) return false;
            teeth.forEach(function (tnum) { marks[tnum] = status; });
            selectedTeeth = {};
            eraseMode = false;
            return true;
        },
        clearOrToggleErase: function () {
            var teeth = selectedList();
            if (teeth.length) {
                teeth.forEach(function (tnum) { delete marks[tnum]; });
                selectedTeeth = {};
                eraseMode = false;
                return 'cleared-selection';
            }
            eraseMode = !eraseMode;
            return eraseMode ? 'erase-on' : 'erase-off';
        },
        unmark: function (tooth) {
            delete marks[String(tooth)];
        }
    };

    $(function () {
        if ($('#dce-editor').length) {
            window.initDentalChartEditor();
        }
    });

})(window, jQuery);
