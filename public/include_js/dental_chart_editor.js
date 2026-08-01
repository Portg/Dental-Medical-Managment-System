/**
 * FDI Dental Chart Editor
 * =======================
 * Same visual language as medical_cases tooth selector.
 * Flow: pick a status → click teeth to apply; "清除" clears a tooth.
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

    // Prefer whole-tooth statuses when aggregating legacy multi-surface rows
    var STATUS_PRIORITY = ['missing', 'implant', 'impacted', 'crown', 'rct', 'filled', 'caries'];

    var selectedStatus = 'caries';
    var marks = {}; // toothNumber -> statusKey

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

    function applyToothStyle($el, status) {
        $el.removeClass('marked');
        $el.css({ background: '', borderColor: '', color: '' });
        $el.find('.dce-mark').text('');
        if (!status || !STATUS_MAP[status]) return;
        var meta = STATUS_MAP[status];
        $el.addClass('marked').css({
            background: meta.bg,
            borderColor: meta.bg
        });
        $el.find('.dce-mark').text(shortLabel(status));
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

    function setActiveStatus(status) {
        selectedStatus = status;
        var $btn = $('.dce-status-btn[data-status="' + status + '"]');
        $('.dce-status-btn').removeClass('active');
        $btn.addClass('active');

        var label = $btn.data('label') || status;
        var bg = $btn.data('bg');
        var $chip = $('#dce-tool-chip');
        var $swatch = $('#dce-tool-swatch');
        $('#dce-tool-label').text(label);
        if (status === 'clear') {
            $chip.addClass('is-clear');
            $swatch.css('background', 'transparent');
        } else {
            $chip.removeClass('is-clear');
            $swatch.css('background', bg || '#EAB308');
        }
    }

    function onToothClick() {
        var tooth = String($(this).data('tooth'));
        if (selectedStatus === 'clear') {
            delete marks[tooth];
            applyToothStyle($(this), null);
        } else if (marks[tooth] === selectedStatus) {
            // Toggle off if clicking same status again
            delete marks[tooth];
            applyToothStyle($(this), null);
        } else {
            marks[tooth] = selectedStatus;
            applyToothStyle($(this), selectedStatus);
        }
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
            $('.dce-tooth').each(function () {
                var tooth = String($(this).data('tooth'));
                applyToothStyle($(this), marks[tooth] || null);
            });
            refreshSummary();
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
            setActiveStatus($(this).data('status'));
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
        setActiveStatus(selectedStatus);
        refreshSummary();
        loadChart();
    };

    $(function () {
        if ($('#dce-editor').length) {
            window.initDentalChartEditor();
        }
    });

})(window, jQuery);
