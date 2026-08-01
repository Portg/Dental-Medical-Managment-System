/**
 * FDI Dental Chart Editor
 * =======================
 * One model: pick a tool → click teeth to paint / erase.
 * Same toggle feel as the medical-case tooth picker.
 *
 * Depends: jQuery, LanguageManager, csrf meta, #global_appointment_id
 */
(function (window, $) {
    'use strict';

    var STATUS_MAP = {
        caries:   { color: '2',  bg: '#EAB308', shortKey: 'short_caries', labelKey: 'caries' },
        filled:   { color: '1',  bg: '#EF4444', shortKey: 'short_filled', labelKey: 'filling' },
        rct:      { color: '3',  bg: '#F97316', shortKey: 'short_rct', labelKey: 'endodontics' },
        crown:    { color: '8',  bg: '#2563EB', shortKey: 'short_crown', labelKey: 'crown' },
        missing:  { color: '4',  bg: '#F43F5E', shortKey: 'short_missing', labelKey: 'absent' },
        implant:  { color: '6',  bg: '#A855F7', shortKey: 'short_implant', labelKey: 'implant' },
        impacted: { color: '11', bg: '#14B8A6', shortKey: 'short_impacted', labelKey: 'impacted_teeth' }
    };

    var COLOR_TO_STATUS = {
        '1': 'filled', '2': 'caries', '3': 'rct', '4': 'missing',
        '6': 'implant', '8': 'crown', '11': 'impacted'
    };

    var STATUS_PRIORITY = ['missing', 'implant', 'impacted', 'crown', 'rct', 'filled', 'caries'];

    var marks = {};       // toothNumber -> statusKey
    var activeTool = null; // statusKey | 'clear' | null
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
        return t(meta.shortKey, t(meta.labelKey, status));
    }

    function toolLabel(tool) {
        if (tool === 'clear') return t('clear_mark', '清除');
        if (tool && STATUS_MAP[tool]) return t(STATUS_MAP[tool].labelKey, tool);
        return t('pick_tool', '请选择状态');
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function appointmentId() {
        return ($('#global_appointment_id').val() || '').trim();
    }

    function markedList() {
        return Object.keys(marks).sort(function (a, b) { return Number(a) - Number(b); });
    }

    function applyToothStyle($el, status) {
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

    function refreshAllTeeth() {
        $('.dce-tooth').each(function () {
            var tooth = String($(this).data('tooth'));
            applyToothStyle($(this), marks[tooth] || null);
        });
    }

    function refreshToolChip() {
        var $chip = $('#dce-tool-chip');
        var $label = $('#dce-tool-label');
        var $swatch = $('#dce-tool-swatch');
        var $btns = $('.dce-status-btn');

        $btns.removeClass('is-active');
        if (!activeTool) {
            $chip.addClass('is-empty').removeClass('is-erase');
            $label.text(t('pick_tool', '请选择状态'));
            $swatch.css({ background: 'transparent', borderStyle: 'dashed' }).html('');
            return;
        }

        $chip.removeClass('is-empty');
        $btns.filter('[data-status="' + activeTool + '"]').addClass('is-active');
        $label.text(t('current_tool', '当前') + '：' + toolLabel(activeTool));

        if (activeTool === 'clear') {
            $chip.addClass('is-erase');
            $swatch.css({ background: '#F3F4F6', borderStyle: 'dashed' }).html('<i class="fa fa-eraser"></i>');
        } else {
            $chip.removeClass('is-erase');
            $swatch.css({
                background: STATUS_MAP[activeTool].bg,
                borderStyle: 'solid'
            }).html('');
        }
    }

    function refreshSummary() {
        var $sum = $('#dce-summary');
        var teeth = markedList();
        if (!teeth.length) {
            $sum.html(t('no_marks_yet', '尚未标记牙位'));
            return;
        }
        var html = '<strong>' + t('marked_teeth', '已标记') + '：</strong>';
        teeth.forEach(function (tooth) {
            html += '<span class="dce-mark-chip" data-tooth="' + tooth + '" title="' +
                t('unmark_tooth', '取消标记') + '">' +
                tooth + ' ' + shortLabel(marks[tooth]) +
                '<button type="button" class="dce-mark-remove" data-tooth="' + tooth +
                '" aria-label="' + t('unmark_tooth', '取消标记') + '">&times;</button></span>';
        });
        $sum.html(html);
    }

    function syncUI() {
        refreshAllTeeth();
        refreshToolChip();
        refreshSummary();
    }

    function setTool(tool) {
        // Click same tool again to deselect tool
        activeTool = (activeTool === tool) ? null : tool;
        refreshToolChip();
    }

    function paintTooth(tooth) {
        tooth = String(tooth);
        if (!activeTool) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(t('pick_tool_first', '请先选择上方状态'));
            }
            return;
        }
        if (activeTool === 'clear') {
            delete marks[tooth];
        } else {
            // Same status again on a marked tooth → unmark (toggle off)
            if (marks[tooth] === activeTool) {
                delete marks[tooth];
            } else {
                marks[tooth] = activeTool;
            }
        }
        var $el = $('.dce-tooth[data-tooth="' + tooth + '"]');
        if ($el.length) applyToothStyle($el, marks[tooth] || null);
        refreshSummary();
    }

    function unmarkTooth(tooth) {
        tooth = String(tooth);
        delete marks[tooth];
        var $el = $('.dce-tooth[data-tooth="' + tooth + '"]');
        if ($el.length) applyToothStyle($el, null);
        refreshSummary();
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
        $(document).on('click.dce', '.dce-status-btn', function () {
            setTool($(this).data('status'));
        });
        $(document).on('click.dce', '.dce-tab', function () {
            var target = $(this).data('target');
            $('.dce-tab').removeClass('active');
            $(this).addClass('active');
            $('.dce-panel').removeClass('active');
            $('#' + target).addClass('active');
        });
        $(document).on('click.dce', '.dce-tooth', function () {
            paintTooth($(this).data('tooth'));
        });
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

    window.__dceTestApi = {
        reset: function () { marks = {}; activeTool = null; },
        getState: function () {
            return {
                marks: Object.assign({}, marks),
                tool: activeTool,
                summaryKind: markedList().length ? 'marked' : 'empty'
            };
        },
        setTool: function (tool) { setTool(tool); },
        paint: function (tooth) { paintTooth(tooth); },
        unmark: function (tooth) { unmarkTooth(tooth); }
    };

    $(function () {
        if ($('#dce-editor').length) {
            window.initDentalChartEditor();
        }
    });

})(window, jQuery);
