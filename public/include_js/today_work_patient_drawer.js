/**
 * Today Work - Patient Detail Drawer
 * ====================================
 * Right-side drawer showing patient summary with 4 tabs:
 *   1. Basic Info  2. Billing  3. Visits  4. Follow-ups
 *
 * Depends on: jQuery, LanguageManager
 */
(function (window) {
    'use strict';

    var currentPatientId = null;

    /**
     * Open the patient drawer and load data.
     */
    window.openPatientDrawer = function (patientId) {
        currentPatientId = patientId;
        $('#patient-drawer-overlay').addClass('open');
        $('#patient-drawer').addClass('open');
        $('#patient-drawer-loading').show();
        $('#patient-drawer-content').hide();

        $.getJSON('/today-work/patient-summary/' + patientId, function (data) {
            renderPatientDrawer(data);
            $('#patient-drawer-loading').hide();
            $('#patient-drawer-content').show();
        }).fail(function () {
            $('#patient-drawer-loading').hide();
            $('#patient-drawer-content').html('<div class="text-center text-danger" style="padding:40px;">' + LanguageManager.trans('common.error_message') + '</div>').show();
        });
    };

    /**
     * Close the patient drawer.
     */
    window.closePatientDrawer = function () {
        $('#patient-drawer-overlay').removeClass('open');
        $('#patient-drawer').removeClass('open');
        currentPatientId = null;
    };

    /**
     * Render all drawer content.
     */
    function renderPatientDrawer(data) {
        // Header
        var genderIcon = data.gender === 'Male' ? '<i class="fa fa-mars" style="color:#2196F3"></i>' : '<i class="fa fa-venus" style="color:#E91E63"></i>';
        var age = data.dob ? calcAge(data.dob) : '';
        var ageText = age ? ' · ' + age + LanguageManager.trans('today_work.years_old') : '';
        var memberBadge = data.member_status === 'Active' ? ' <span class="label label-warning" style="font-size:10px;">VIP</span>' : '';

        $('#pd-name').html(escHtml(data.full_name) + memberBadge);
        $('#pd-meta').html(genderIcon + ageText + ' · ' + escHtml(data.patient_no));
        $('#pd-phone').text(data.phone_no || '-');

        // Allergy warning
        if (data.allergies) {
            $('#pd-allergy').html('<i class="fa fa-exclamation-triangle"></i> ' + escHtml(data.allergies)).show();
        } else {
            $('#pd-allergy').hide();
        }

        // Detail link
        $('#pd-detail-link').attr('href', '/patients/' + data.id);

        // Tab: Visits
        var visitsHtml = '';
        if (data.appointments && data.appointments.length > 0) {
            data.appointments.forEach(function (a) {
                visitsHtml += '<div class="pd-record-item">';
                visitsHtml += '<div class="pd-record-date">' + escHtml(a.date) + ' ' + escHtml(a.time) + '</div>';
                visitsHtml += '<div class="pd-record-detail">' + escHtml(a.doctor) + ' · ' + escHtml(a.service) + '</div>';
                visitsHtml += '</div>';
            });
        } else {
            visitsHtml = '<div class="pd-empty">' + LanguageManager.trans('today_work.no_records') + '</div>';
        }
        $('#pd-tab-visits').html(visitsHtml);

        // Tab: Billing
        var billingHtml = '';
        if (data.invoices && data.invoices.length > 0) {
            data.invoices.forEach(function (inv) {
                var paidClass = inv.paid_amount >= inv.total_amount ? 'text-success' : 'text-warning';
                billingHtml += '<div class="pd-record-item">';
                billingHtml += '<div class="pd-record-date">' + escHtml(inv.created_at) + ' <span class="text-muted">#' + escHtml(inv.invoice_no) + '</span></div>';
                billingHtml += '<div class="pd-record-detail">';
                billingHtml += LanguageManager.trans('today_work.total') + ': ¥' + Number(inv.total_amount).toFixed(2);
                billingHtml += ' <span class="' + paidClass + '">' + LanguageManager.trans('today_work.paid') + ': ¥' + Number(inv.paid_amount).toFixed(2) + '</span>';
                billingHtml += '</div>';
                billingHtml += '</div>';
            });
        } else {
            billingHtml = '<div class="pd-empty">' + LanguageManager.trans('today_work.no_records') + '</div>';
        }
        $('#pd-tab-billing').html(billingHtml);
    }

    /**
     * Calculate age from date of birth.
     */
    function calcAge(dob) {
        var birth = new Date(dob);
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        return age;
    }

    /**
     * HTML escape.
     */
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Close on overlay click
    $(document).on('click', '#patient-drawer-overlay', function () {
        closePatientDrawer();
    });

    // Close on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#patient-drawer').hasClass('open')) {
            closePatientDrawer();
        }
    });

})(window);
