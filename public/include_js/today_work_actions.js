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
    window.quickMedicalCase = function(patientId, appointmentId) {
        // Reset form and prefill
        var $form = $('#medical_case_form');
        if ($form.length) {
            $form[0].reset();
            $form.find('[name="patient_id"]').val(patientId).trigger('change');
            $form.find('[name="case_date"]').val(new Date().toISOString().slice(0, 10));
        }
        $('#medical_case_modal').modal('show');
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
    window.quickInvoice = function(appointmentId) {
        var $form = $('#New-invoice-form');
        if ($form.length) {
            $form[0].reset();
            $('#invoicing_appointment_id').val(appointmentId);
            // Clear dynamic rows except the first template
            $form.find('.invoice-item-row:not(:first)').remove();
        }
        $('#New-invoice-modal').modal('show');
    };

    // ── Next Appointment ─────────────────────────────────
    window.quickNextAppointment = function(patientId) {
        if (typeof openAppointmentDrawer === 'function') {
            openAppointmentDrawer({ patient_id: patientId });
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
