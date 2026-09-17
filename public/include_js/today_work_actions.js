/**
 * Today Work - Quick Action Functions
 * ====================================
 * Standalone JS for the Today's Work page.
 * Does NOT reuse prescriptions.js / invoicing.js (they have page-specific side effects).
 *
 * Depends on:
 *   - jQuery, toastr, bootbox (confirm dialogs)
 *   - csrfToken (set in the page)
 *   - twTable (DataTable instance, set in the page)
 *   - refreshStats() (set in the page)
 */
(function(window) {
    'use strict';

    /**
     * 工作台统一空态：列表 / 看板整板 / 侧栏 Tab 共用。
     * opts: { icon, title, desc, actionsHtml, compact, panel }
     * panel=true：白底虚线卡片，避免漂在页面灰底上像一大块空白。
     */
    window.twEmptyState = function (opts) {
        opts = opts || {};
        var icon = opts.icon || 'fa-inbox';
        var cls = 'tw-empty-state';
        if (opts.compact) cls += ' is-compact';
        if (opts.panel) cls += ' is-panel';
        var html = '<div class="' + cls + '">';
        html += '<div class="tw-empty-state-icon"><i class="fa ' + icon + '"></i></div>';
        if (opts.title) {
            html += '<div class="tw-empty-state-title">' + opts.title + '</div>';
        }
        if (opts.desc) {
            html += '<div class="tw-empty-state-desc">' + opts.desc + '</div>';
        }
        if (opts.actionsHtml) {
            html += '<div class="tw-empty-state-actions">' + opts.actionsHtml + '</div>';
        }
        html += '</div>';
        return html;
    };

    /** 今日无就诊时的行动：优先新预约；挂号仍保留给到店临挂 */
    window.twEmptyDayActions = function () {
        var html = '';
        if (typeof openAppointmentDrawer === 'function') {
            html += '<button type="button" class="btn btn-sm btn-primary" onclick="openAppointmentDrawer()">'
                + '<i class="fa fa-calendar-plus-o"></i> '
                + LanguageManager.trans('today_work.new_appointment', '新建预约')
                + '</button>';
        }
        if (typeof openRegistrationModal === 'function') {
            html += '<button type="button" class="btn btn-sm btn-default" onclick="openRegistrationModal()">'
                + '<i class="fa fa-sign-in"></i> '
                + LanguageManager.trans('today_work.register', '挂号')
                + '</button>';
        }
        return html;
    };

    function afterAction() {
        if (typeof twTable !== 'undefined') {
            twTable.ajax.reload(null, false);
        }
        if (typeof refreshStats === 'function') {
            refreshStats();
        }
    }

    function ajaxPost(url, data, successMsg) {
        data._token = csrfToken;
        $.post(url, data, function(response) {
            if (response.status === 'success') {
                toastr.success(successMsg || response.message);
                afterAction();
            } else {
                toastr.error(response.message || 'Error');
            }
        }).fail(function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error';
            toastr.error(msg);
        });
    }

    // ── Patient Registration ──────────────────────────────
    window.quickRegisterPatient = function() {
        if (typeof resetPatientFormToCreateMode === 'function') {
            resetPatientFormToCreateMode();
        }
        $('#patients-modal').modal('show');
    };

    // ── Check In ──────────────────────────────────────────
    window.quickCheckIn = function(appointmentId) {
        ajaxPost('/waiting-queue/check-in', {
            appointment_id: appointmentId
        });
    };

    // ── Call Patient ──────────────────────────────────────
    window.quickCall = function(queueId) {
        // If chairs are available, show a simple prompt; otherwise call directly
        if (typeof bootbox !== 'undefined') {
            bootbox.prompt({
                title: LanguageManager.trans('today_work.select_chair_hint'),
                inputType: 'select',
                inputOptions: getChairOptions(),
                callback: function(chairId) {
                    if (chairId !== null) {
                        ajaxPost('/waiting-queue/' + queueId + '/call', {
                            chair_id: chairId || null
                        });
                    }
                }
            });
        } else {
            ajaxPost('/waiting-queue/' + queueId + '/call', {});
        }
    };

    // ── Start Treatment ──────────────────────────────────
    window.quickStartTreatment = function(queueId) {
        ajaxPost('/waiting-queue/' + queueId + '/start', {});
    };

    // ── Complete Treatment ────────────────────────────────
    window.quickCompleteTreatment = function(queueId) {
        ajaxPost('/waiting-queue/' + queueId + '/complete', {});
    };

    // ── Rollback（视频「回退」）────────────────────────────
    window.quickRollback = function(queueId) {
        if (confirm(LanguageManager.trans('today_work.confirm_rollback'))) {
            ajaxPost('/waiting-queue/' + queueId + '/rollback', {},
                LanguageManager.trans('today_work.rollback_success'));
        }
    };

    // ── Cancel Queue ─────────────────────────────────────
    window.quickCancelQueue = function(queueId) {
        if (confirm(LanguageManager.trans('today_work.confirm_cancel'))) {
            ajaxPost('/waiting-queue/' + queueId + '/cancel', {});
        }
    };

    // ── Mark No Show ─────────────────────────────────────
    window.quickNoShow = function(appointmentId) {
        if (confirm(LanguageManager.trans('today_work.confirm_no_show'))) {
            ajaxPost('/today-work/mark-no-show/' + appointmentId, {});
        }
    };

    // ── Medical Case ─────────────────────────────────────
    /**
     * 开病历 —— 直接进病历页，带上这次就诊。
     *
     * 原来这里弹的是一个只有「标题 / 日期 / 患者 / 医生 / 主诉 / 现病史」的小弹窗，
     * 而真正的病历页（分段录入牙位+文字、牙位十字图、ICD 诊断、治疗计划、
     * 就诊次数、带入本次）在 medical-case-new/{patient}，从工作台根本走不到。
     * 医生在工作台点「病历」，要的就是那一页 —— 参考视频，点病历直接进病历。
     *
     * appointmentId 一并带过去：一次就诊一份病历，不带的话保存出来的病历
     * 不知道对应今天哪一次看诊。原实现整个参数都没用。
     */
    window.quickMedicalCase = function(patientId, appointmentId) {
        var url = '/medical-case-new/' + patientId;
        if (appointmentId) {
            url += '?appointment_id=' + appointmentId;
        }
        window.location.href = url;
    };

    // ── Prescription ─────────────────────────────────────
    window.quickPrescription = function(appointmentId) {
        var $form = $('#prescription-form');
        if ($form.length) {
            $form[0].reset();
            $('#prescription_appointment_id').val(appointmentId);
            // Clear dynamic rows except the first template
            $form.find('.prescription-item:not(:first)').remove();
        }
        $('#prescription-modal').modal('show');
    };

    // ── Invoice ──────────────────────────────────────────
    /**
     * 收费 = 进患者页「收费」Tab 的划价面板（billing.partials.charge_panel）。
     *
     * 不要跳 /medical-treatment/…：那是诊疗/预约页，前台只想划价收款却整页诊疗
     * 壳子，体验像进错门。划价 UI 早就在患者页做好了（与诊疗页共用同一 partial），
     * 缺的是入口对齐，不是再造一页。
     *
     * appointmentId 仍要带上：账单挂到这次就诊，诊疗页「本次已划价」和按就诊
     * 统计才对得上。患者页 BillingModule.init 会读 ?appointment_id=。
     */
    window.quickInvoice = function(patientId, appointmentId) {
        if (!patientId) {
            return;
        }
        var url = '/patients/' + patientId + '#billing_tab';
        if (appointmentId) {
            url = '/patients/' + patientId + '?appointment_id=' + appointmentId + '#billing_tab';
        }
        window.location.href = url;
    };

    // ── Next Appointment ─────────────────────────────────
    // date / followupId 只有该患者存在未约上的复诊待办时才会传进来：
    // 带上日期省得前台再去病历里翻，带上 followupId 是为了约成后自动闭环那条待办。
    window.quickNextAppointment = function(patientId, date, followupId, doctorId) {
        if (typeof openAppointmentDrawer === 'function') {
            openAppointmentDrawer({
                patient_id: patientId,
                date: date || undefined,
                followup_id: followupId || undefined,
                // 病人刚看完的就是这位医生，默认带上；抽屉里改成别人也行
                doctor_id: doctorId || undefined
            });
        }
    };

    /**
     * 「就诊流程」单列下一步：与 TodayWorkService::nextStepOptions 保持同序同动作。
     * 列表由服务端渲染；看板复用这份，避免两套状态链各长各的。
     */
    window.twNextStepOptions = function (status, aptId, queueId) {
        switch (status) {
            case 'not_arrived':
                return [
                    ['quickCheckIn', LanguageManager.trans('today_work.check_in'), aptId],
                    ['quickNoShow', LanguageManager.trans('today_work.mark_no_show'), aptId]
                ];
            case 'waiting':
                return [
                    ['quickCall', LanguageManager.trans('today_work.call'), queueId],
                    ['quickRollback', LanguageManager.trans('today_work.rollback'), queueId],
                    ['quickCancelQueue', LanguageManager.trans('common.cancel'), queueId]
                ];
            case 'called':
                return [
                    ['quickStartTreatment', LanguageManager.trans('today_work.start_treatment'), queueId],
                    ['quickCall', LanguageManager.trans('today_work.recall'), queueId],
                    ['quickRollback', LanguageManager.trans('today_work.rollback'), queueId]
                ];
            case 'in_treatment':
                return [
                    ['quickCompleteTreatment', LanguageManager.trans('today_work.complete_treatment'), queueId],
                    ['quickRollback', LanguageManager.trans('today_work.rollback'), queueId]
                ];
            case 'completed':
                return [
                    ['quickRollback', LanguageManager.trans('today_work.rollback'), queueId]
                ];
            default:
                return [];
        }
    };

    window.twRenderNextStepSelect = function (status, aptId, queueId) {
        var options = window.twNextStepOptions(status, aptId, queueId);
        if (!options.length) {
            return '';
        }
        var closed = status === 'completed'
            ? LanguageManager.trans('today_work.completed')
            : options[0][1];
        var html = '<select class="tw-next-dd" aria-label="'
            + LanguageManager.trans('today_work.col_flow', '就诊流程')
            + '" onchange="twOnNextStepChange(this);event.stopPropagation();">'
            + '<option value="" selected disabled hidden>' + closed + '</option>';
        options.forEach(function (row) {
            html += '<option value="' + row[0] + '" data-arg="' + row[2] + '">' + row[1] + '</option>';
        });
        html += '</select>';
        return html;
    };

    /** 「下一步」下拉：执行所选流程动作后复位到展示态 */
    window.twOnNextStepChange = function (sel) {
        var opt = sel.options[sel.selectedIndex];
        var fn = opt && opt.value;
        var arg = opt ? opt.getAttribute('data-arg') : null;
        sel.selectedIndex = 0;
        if (!fn || typeof window[fn] !== 'function') {
            return;
        }
        var n = arg === null || arg === '' ? undefined : Number(arg);
        if (n !== undefined && !isNaN(n)) {
            window[fn](n);
        } else {
            window[fn](arg);
        }
    };

    // ── Helper: get chair options for bootbox select ─────
    function getChairOptions() {
        var options = [{ text: '---', value: '' }];
        // Try to fetch from a cached list or make a synchronous call
        try {
            $.ajax({
                url: '/api/chairs',
                async: false,
                dataType: 'json',
                success: function(data) {
                    data.forEach(function(chair) {
                        options.push({ text: chair.text, value: chair.id });
                    });
                }
            });
        } catch (e) {
            // Fallback: no chair selection
        }
        return options;
    }

})(window);
