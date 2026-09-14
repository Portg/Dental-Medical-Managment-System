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
     * 收费 = 到诊疗页的划价面板去开单。
     *
     * 原来这里开的是 appointments.invoices.create 那个老弹窗，而那条路已经是死的：
     * 92f20f8「划价+收款搬到诊疗页，两套开单 UI 合成一套」把 save_invoice /
     * #addInvoiceItem / #service 的 select2 一起删了（见 invoicing.js 顶部注释），
     * 预约页留了自己的内联副本所以还活着，工作台这边却还指着那具尸体 ——
     * 弹窗能弹出来，但项目选不了、「添加更多」没反应、「生成账单」点了什么都不发生。
     * 实测确认：save_invoice 是 undefined。
     *
     * 顺带解决两个重复 id：那个弹窗自带 #btnSave 和 #doctor_id，与页面上别处撞号。
     */
    window.quickInvoice = function(appointmentId) {
        window.location.href = '/medical-treatment/' + appointmentId + '#dental_billing_tab';
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
