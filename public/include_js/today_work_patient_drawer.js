/**
 * Today Work - Patient Detail Drawer
 * ====================================
 * Right-side drawer showing patient summary with 2 tabs:
 *   1. Visits (clinical visit cards)  2. Billing
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
            $('#patient-drawer-content').html(
                '<div class="text-center text-danger" style="padding:40px;">' +
                LanguageManager.trans('common.error_message') +
                '</div>'
            ).show();
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
        var genderIcon = data.gender === 'Male'
            ? '<i class="fa fa-mars" style="color:#2196F3"></i>'
            : '<i class="fa fa-venus" style="color:#E91E63"></i>';
        var age = data.dob ? calcAge(data.dob) : '';
        var ageText = age ? ' · ' + age + LanguageManager.trans('today_work.years_old') : '';
        var memberBadge = data.member_status === 'Active'
            ? ' <span class="label label-warning" style="font-size:10px;">VIP</span>'
            : '';

        $('#pd-name').html(escHtml(data.full_name) + memberBadge);
        $('#pd-meta').html(genderIcon + ageText + ' · ' + escHtml(data.patient_no));
        $('#pd-phone').text(data.phone_no || '-');

        if (data.allergies) {
            $('#pd-allergy').html('<i class="fa fa-exclamation-triangle"></i> ' + escHtml(data.allergies)).show();
        } else {
            $('#pd-allergy').hide();
        }

        $('#pd-detail-link').attr('href', '/patients/' + data.id);

        $('#pd-tab-visits').html(renderVisits(data.appointments || []));
        $('#pd-tab-billing').html(renderBilling(data.invoices || []));
    }

    function renderVisits(appointments) {
        if (!appointments.length) {
            return '<div class="pd-empty">' + LanguageManager.trans('today_work.no_records') + '</div>';
        }

        var html = '';
        appointments.forEach(function (a) {
            var typeLabel = a.appointment_type_label || '';
            var statusLabel = a.status_label || '';
            var summary = a.summary || LanguageManager.trans('today_work.no_clinical_summary');
            var metaParts = [];
            if (a.doctor) metaParts.push(a.doctor);
            if (a.service) metaParts.push(a.service);
            var meta = metaParts.join(' · ');

            html += '<div class="pd-record-item pd-visit-item">';
            html += '<a class="pd-visit-link" href="' + escAttr(a.treatment_url || ('/medical-treatment/' + a.id)) + '">';
            html += '<div class="pd-visit-header">';
            html += '<span class="pd-visit-date">' + escHtml(a.date || '') + '</span>';
            html += '<span class="pd-visit-badges">';
            if (typeLabel) {
                html += '<span class="pd-badge pd-badge-type">' + escHtml(typeLabel) + '</span>';
            }
            if (statusLabel) {
                html += '<span class="pd-badge pd-badge-status status-' + escAttr(statusClass(a.status)) + '">' +
                    escHtml(statusLabel) + '</span>';
            }
            html += '</span>';
            html += '</div>';
            html += '<div class="pd-visit-summary">' + escHtml(summary) + '</div>';
            if (meta) {
                html += '<div class="pd-visit-meta">' + escHtml(meta) + '</div>';
            }
            html += '</a>';
            if (a.medical_case_url) {
                html += '<a class="pd-visit-case-link" href="' + escAttr(a.medical_case_url) + '">' +
                    LanguageManager.trans('today_work.open_medical_case') + '</a>';
            }
            html += '</div>';
        });
        return html;
    }

    function renderBilling(invoices) {
        if (!invoices.length) {
            return '<div class="pd-empty">' + LanguageManager.trans('today_work.no_records') + '</div>';
        }

        var html = '';
        invoices.forEach(function (inv) {
            var paidClass = inv.paid_amount >= inv.total_amount ? 'text-success' : 'text-warning';
            html += '<div class="pd-record-item">';
            html += '<div class="pd-record-date">' + escHtml(inv.created_at) +
                ' <span class="text-muted">#' + escHtml(inv.invoice_no) + '</span></div>';
            html += '<div class="pd-record-detail">';
            html += LanguageManager.trans('today_work.total') + ': ¥' + Number(inv.total_amount).toFixed(2);
            html += ' <span class="' + paidClass + '">' + LanguageManager.trans('today_work.paid') +
                ': ¥' + Number(inv.paid_amount).toFixed(2) + '</span>';
            html += '</div>';
            html += '</div>';
        });
        return html;
    }

    function statusClass(status) {
        if (!status) return 'default';
        return String(status).toLowerCase().replace(/\s+/g, '-');
    }

    function calcAge(dob) {
        var birth = new Date(dob);
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        return age;
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return escHtml(str).replace(/"/g, '&quot;');
    }

    $(document).on('click', '#patient-drawer-overlay', function () {
        closePatientDrawer();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#patient-drawer').hasClass('open')) {
            closePatientDrawer();
        }
    });

})(window);
