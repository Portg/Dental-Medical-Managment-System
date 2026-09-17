/**
 * Today Work - Kanban View
 * =========================
 * Fetches grouped patient data and renders kanban columns.
 *
 * Depends on:
 *   - jQuery, LanguageManager
 *   - today_work_actions.js (quick action functions)
 *   - refreshStats() (from page)
 */
(function (window) {
    'use strict';

    var kanbanData = {};
    var cardCollapsed = localStorage.getItem('tw_kanban_cards_collapsed') === '1';

    // Status column order
    var STATUS_ORDER = ['not_arrived', 'waiting', 'called', 'in_treatment', 'completed', 'no_show'];

    // Badge colors per status
    var BADGE_COLORS = {
        not_arrived:  'warning',
        waiting:      'info',
        called:       'primary',
        in_treatment: 'success',
        completed:    'default',
        no_show:      'danger'
    };

    /**
     * Load kanban data from server and render.
     */
    window.loadKanbanData = function () {
        var params = {};
        var $date = $('#tw-date-filter');
        var $doctor = $('#tw-doctor-filter');
        var $search = $('#tw-search');
        if ($date.length && $date.val()) params.date = $date.val();
        if ($doctor.length && $doctor.val()) params.doctor_id = $doctor.val();
        if ($search.length && $search.val()) params.search_patient = $.trim($search.val());

        $.getJSON('/today-work/kanban-data', params, function (data) {
            kanbanData = data;
            renderKanban(data);
        });
    };

    /**
     * Render all kanban columns.
     */
    function renderKanban(data) {
        var total = 0;
        STATUS_ORDER.forEach(function (status) {
            total += (data[status] || []).length;
        });

        var $view = $('#tw-kanban-view');
        var $row = $view.find('.kanban-row');
        var $boardEmpty = $view.find('.tw-kanban-board-empty');

        if (!$boardEmpty.length) {
            $boardEmpty = $('<div class="tw-kanban-board-empty"></div>').prependTo($view);
        }

        if (total === 0) {
            $view.addClass('is-board-empty');
            $boardEmpty.html(twEmptyState({
                icon: 'fa-th-large',
                title: LanguageManager.trans('today_work.kanban_board_empty_title', '今日暂无就诊卡片'),
                actionsHtml: twEmptyDayActions(),
                panel: true
            })).show();
            $row.hide();
            // 角标仍清零，避免残留上次数字
            STATUS_ORDER.forEach(function (status) {
                $('#kanban-col-' + status).find('.kanban-col-header .badge').text('0');
            });
            return;
        }

        $view.removeClass('is-board-empty');
        $boardEmpty.hide().empty();
        $row.show();

        STATUS_ORDER.forEach(function (status) {
            var items = data[status] || [];
            var $col = $('#kanban-col-' + status);
            var $body = $col.find('.kanban-col-body');
            var $badge = $col.find('.kanban-col-header .badge');

            $badge.text(items.length);
            $body.empty();

            if (items.length === 0) {
                $body.html(
                    '<div class="kanban-col-empty">' +
                    '<span class="kanban-col-empty-line"></span>' +
                    '<span class="kanban-col-empty-text">' +
                    LanguageManager.trans('today_work.kanban_empty', '暂无') +
                    '</span></div>'
                );
                return;
            }

            items.forEach(function (item) {
                $body.append(buildCard(item, status));
            });
        });
    }

    /**
     * Build a single kanban card HTML.
     */
    function buildCard(item, status) {
        var collapsedClass = cardCollapsed ? ' collapsed' : '';
        var typeTag = '';
        if (item.appointment_type === 'revisit') {
            typeTag = '<span class="card-tag revisit">' + LanguageManager.trans('today_work.revisit') + '</span>';
        } else {
            typeTag = '<span class="card-tag first-visit">' + LanguageManager.trans('today_work.first_visit') + '</span>';
        }

        var durationHtml = '';
        if (status === 'in_treatment' && item.check_in_time) {
            durationHtml = '<div class="card-duration" data-checkin="' + item.check_in_time + '">' + calcDuration(item.check_in_time) + '</div>';
        }

        var html = '<div class="kanban-card' + collapsedClass + '" data-appointment-id="' + item.appointment_id + '" data-patient-id="' + item.patient_id + '">';
        html += '<div class="kanban-card-header">';
        html += '<span class="patient-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + escHtml(item.patient_name) + '</span>';
        html += '<span class="appointment-time">' + escHtml(item.start_time) + '</span>';
        html += '</div>';
        html += '<div class="kanban-card-body">';
        html += '<div class="card-info">' + typeTag + escHtml(item.doctor_name) + (item.service ? ' · ' + escHtml(item.service) : '') + '</div>';
        html += durationHtml;
        html += '</div>';
        html += '<div class="kanban-card-actions">' + buildCardActions(item, status) + '</div>';
        html += '</div>';

        return html;
    }

    /**
     * 看板卡片动作：流程走单列「下一步」下拉（与列表同链）；病历/收费旁挂图标。
     */
    function buildCardActions(item, status) {
        var btns = '';
        if (typeof twRenderNextStepSelect === 'function') {
            btns += twRenderNextStepSelect(status, item.appointment_id, item.queue_id);
        }
        // 与列表旁挂列一致：病历 / 收费不塞进流程下拉
        btns += ' <button class="btn btn-xs btn-default tw-icon-btn" title="'
            + LanguageManager.trans('today_work.medical_case')
            + '" onclick="quickMedicalCase(' + item.patient_id + ',' + item.appointment_id + ');event.stopPropagation();">'
            + '<i class="fa fa-file-text-o"></i></button>';
        btns += ' <button class="btn btn-xs btn-default tw-icon-btn" title="'
            + LanguageManager.trans('today_work.invoice')
            + '" onclick="quickInvoice(' + item.patient_id + ',' + item.appointment_id + ');event.stopPropagation();">'
            + '<i class="fa fa-money"></i></button>';
        return btns;
    }

    /**
     * Calculate duration string from check-in time to now.
     */
    function calcDuration(checkInTime) {
        var checkIn = new Date(checkInTime);
        var now = new Date();
        var diffMs = now - checkIn;
        if (diffMs < 0) return '';
        var minutes = Math.floor(diffMs / 60000);
        if (minutes < 60) return minutes + LanguageManager.trans('today_work.minutes');
        var hours = Math.floor(minutes / 60);
        var remainMin = minutes % 60;
        return hours + LanguageManager.trans('today_work.hours') + remainMin + LanguageManager.trans('today_work.minutes');
    }

    /**
     * Update all duration displays (called every 60s).
     */
    window.updateKanbanDurations = function () {
        $('.card-duration[data-checkin]').each(function () {
            var checkIn = $(this).data('checkin');
            if (checkIn) {
                $(this).text(calcDuration(checkIn));
            }
        });
    };

    /**
     * Toggle card collapse state.
     */
    window.toggleKanbanCollapse = function () {
        cardCollapsed = !cardCollapsed;
        localStorage.setItem('tw_kanban_cards_collapsed', cardCollapsed ? '1' : '0');
        $('.kanban-card').toggleClass('collapsed', cardCollapsed);
        var $icon = $('#kanban-collapse-btn i');
        $icon.toggleClass('fa-compress', !cardCollapsed);
        $icon.toggleClass('fa-expand', cardCollapsed);
    };

    /**
     * HTML escape helper.
     */
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})(window);
