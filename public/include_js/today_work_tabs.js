/**
 * Today Work — Tab AJAX Loading & Render Functions
 *
 * Handles lazy-loading of info tabs: billing, followups, tomorrow,
 * week-missed, birthdays, doctor-table.
 *
 * Each tab has its OWN filter bar; filter IDs follow the pattern:
 *   #{tab}-date-filter, #{tab}-doctor-filter, #{tab}-status-filter
 */

var tabLoaded = {};

function invalidateTabCache() {
    tabLoaded = {};
}

function initInfoTabs() {
    $('#tw-info-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var tab = $(e.target).data('tab');
        if (tab !== 'today-work') {
            if (!tabLoaded[tab]) {
                loadTabData(tab);
            }
        }
    });
}

/**
 * Build per-tab filter params by reading the tab's own filter elements.
 */
function getTabFilterParams(tab) {
    var params = {};
    var dateVal = $('#' + tab + '-date-filter').val();
    var doctorVal = $('#' + tab + '-doctor-filter').val();
    var statusVal = $('#' + tab + '-status-filter').val();
    var searchVal = $('#' + tab + '-search').val();
    var typeVal = $('#' + tab + '-type-filter').val();
    var startDateVal = $('#' + tab + '-start-date').val();
    var endDateVal = $('#' + tab + '-end-date').val();

    if (dateVal) params.date = dateVal;
    if (doctorVal) params.doctor_id = doctorVal;
    if (statusVal) params.status = statusVal;
    if (searchVal) params.search = searchVal;
    if (typeVal) params.followup_type = typeVal;
    if (startDateVal) params.start_date = startDateVal;
    if (endDateVal) params.end_date = endDateVal;
    return params;
}

function loadTabData(tab) {
    var urlBase = window.twTabUrls || {};
    var url = urlBase[tab];
    if (!url) return;

    var params = getTabFilterParams(tab);

    var $loading = $('#' + tab + '-loading');
    var $content = $('#' + tab + '-content');
    $loading.show();
    $content.hide();

    $.getJSON(url, params, function(data) {
        var html = '';
        switch (tab) {
            case 'billing':      html = renderBillingTab(data); break;
            case 'followups':    html = renderFollowupsTab(data); break;
            case 'tomorrow':     html = renderTomorrowTab(data); break;
            case 'week-missed':  html = renderWeekMissedTab(data); break;
            case 'birthdays':    html = renderBirthdaysTab(data); break;
            case 'paid':       html = renderPaidTab(data); break;
            case 'unpaid':     html = renderUnpaidTab(data); break;
            case 'lab-cases':  html = renderLabCasesTab(data); break;
            case 'doctor-table': html = renderDoctorTableTab(data); break;
        }
        $content.html(html);
        $loading.hide();
        $content.show();
        tabLoaded[tab] = true;
    }).fail(function() {
        $loading.hide();
        $content.html('<div class="tw-tab-empty">' + LanguageManager.trans('common.error_message') + '</div>').show();
    });
}

// ── Render Functions ─────────────────────────────────

function renderBillingTab(data) {
    if (!data.by_method || data.by_method.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.billing_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr><th>' + LanguageManager.trans('today_work.billing_method') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.billing_count') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.billing_amount') + '</th></tr></thead><tbody>';
    data.by_method.forEach(function(item) {
        html += '<tr><td>' + _escHtml(item.method) + '</td>';
        html += '<td>' + item.count + '</td>';
        html += '<td>&yen;' + Number(item.total).toFixed(2) + '</td></tr>';
    });
    html += '<tr style="font-weight:600;background:#f8f9fa;"><td>' + LanguageManager.trans('today_work.billing_total') + '</td>';
    html += '<td>' + data.total_count + '</td>';
    html += '<td>&yen;' + Number(data.total_amount).toFixed(2) + '</td></tr>';
    html += '</tbody></table></div></div>';
    return html;
}

function renderFollowupsTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.followup_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr><th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.doctor') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.followup_type') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.followup_purpose') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.followup_status') + '</th></tr></thead><tbody>';
    data.forEach(function(item) {
        var statusMap = {
            'Completed': {cls: 'label-success', key: 'today_work.followup_completed'},
            'Pending': {cls: 'label-warning', key: 'today_work.followup_pending'},
            'Cancelled': {cls: 'label-default', key: 'today_work.followup_cancelled'},
            'No Response': {cls: 'label-danger', key: 'today_work.followup_no_response'}
        };
        var statusInfo = statusMap[item.status] || {cls: 'label-default', key: 'today_work.followup_pending'};
        var statusClass = statusInfo.cls;
        var statusText = LanguageManager.trans(statusInfo.key);
        html += '<tr>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.doctor_name) + '</td>';
        html += '<td>' + _escHtml(item.patient_phone) + '</td>';
        html += '<td>' + _escHtml(item.followup_type) + '</td>';
        html += '<td>' + _escHtml(item.purpose) + '</td>';
        html += '<td><span class="label ' + statusClass + '">' + statusText + '</span></td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

function renderTomorrowTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.tomorrow_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr><th>' + LanguageManager.trans('common.time') + '</th>';
    html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('common.doctor') + '</th>';
    html += '<th>' + LanguageManager.trans('common.service') + '</th></tr></thead><tbody>';
    data.forEach(function(item) {
        html += '<tr>';
        html += '<td>' + _escHtml(item.start_time) + '</td>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.patient_phone) + '</td>';
        html += '<td>' + _escHtml(item.doctor_name) + '</td>';
        html += '<td>' + _escHtml(item.service) + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

function renderWeekMissedTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.missed_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr><th>' + LanguageManager.trans('today_work.missed_date') + '</th>';
    html += '<th>' + LanguageManager.trans('common.time') + '</th>';
    html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('common.doctor') + '</th>';
    html += '<th>' + LanguageManager.trans('common.service') + '</th></tr></thead><tbody>';
    data.forEach(function(item) {
        html += '<tr>';
        html += '<td>' + _escHtml(item.date) + '</td>';
        html += '<td>' + _escHtml(item.time) + '</td>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.patient_phone) + '</td>';
        html += '<td>' + _escHtml(item.doctor_name) + '</td>';
        html += '<td>' + _escHtml(item.service) + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

function renderBirthdaysTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.birthday_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr><th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.birthday_age') + '</th></tr></thead><tbody>';
    data.forEach(function(item) {
        var genderIcon = item.gender === 'Male' ? '<i class="fa fa-mars" style="color:#2196F3"></i>' : '<i class="fa fa-venus" style="color:#E91E63"></i>';
        html += '<tr>';
        html += '<td>' + genderIcon + ' <span class="clickable-name" onclick="openPatientDrawer(' + item.id + ')">' + _escHtml(item.name) + '</span></td>';
        html += '<td>' + _escHtml(item.phone) + '</td>';
        html += '<td>' + (item.age !== null ? item.age + LanguageManager.trans('today_work.years_old') : '-') + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

function renderDoctorTableTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.doctor_no_data') + '</div>';
    }
    var html = '';
    data.forEach(function(doctor) {
        html += '<div class="doctor-group">';
        html += '<div class="doctor-group-header" onclick="$(this).next(\'.doctor-group-body\').toggle()">';
        html += '<span class="doctor-name"><i class="fa fa-user-md"></i> ' + _escHtml(doctor.doctor_name) + '</span>';
        html += '<span class="doctor-stats">';
        html += '<span>' + LanguageManager.trans('today_work.doctor_total') + ': <b>' + doctor.total + '</b></span>';
        html += '<span style="color:#2196F3;">' + LanguageManager.trans('today_work.doctor_waiting') + ': <b>' + doctor.waiting + '</b></span>';
        html += '<span style="color:#4CAF50;">' + LanguageManager.trans('today_work.doctor_treating') + ': <b>' + doctor.in_treatment + '</b></span>';
        html += '<span style="color:#9E9E9E;">' + LanguageManager.trans('today_work.doctor_done') + ': <b>' + doctor.completed + '</b></span>';
        html += '</span>';
        html += '</div>';
        html += '<div class="doctor-group-body">';
        html += '<table class="table tw-info-table">';
        html += '<thead><tr><th>' + LanguageManager.trans('common.time') + '</th>';
        html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
        html += '<th>' + LanguageManager.trans('common.service') + '</th>';
        html += '<th>' + LanguageManager.trans('common.status') + '</th></tr></thead><tbody>';
        doctor.patients.forEach(function(p) {
            var statusBadges = {
                'not_arrived': 'label-warning',
                'waiting': 'label-info',
                'called': 'label-primary',
                'in_treatment': 'label-success',
                'completed': 'label-default',
                'no_show': 'label-danger'
            };
            var badgeClass = statusBadges[p.status] || 'label-default';
            html += '<tr>';
            html += '<td>' + _escHtml(p.start_time) + '</td>';
            html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + p.patient_id + ')">' + _escHtml(p.patient_name) + '</span></td>';
            html += '<td>' + _escHtml(p.service) + '</td>';
            html += '<td><span class="label ' + badgeClass + '">' + LanguageManager.trans('today_work.' + p.status) + '</span></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div></div>';
    });
    return html;
}

function renderPaidTab(data) {
    if (!data.items || data.items.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.paid_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr>';
    html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.billing_method') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.billing_amount') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.invoice_no') + '</th>';
    html += '</tr></thead><tbody>';
    data.items.forEach(function(item) {
        html += '<tr>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.patient_phone) + '</td>';
        html += '<td>' + _escHtml(item.payment_method) + '</td>';
        html += '<td>&yen;' + Number(item.amount).toFixed(2) + '</td>';
        html += '<td>' + _escHtml(item.invoice_no) + '</td>';
        html += '</tr>';
    });
    html += '<tr style="font-weight:600;background:#f8f9fa;"><td colspan="3">' + LanguageManager.trans('today_work.billing_total') + '</td>';
    html += '<td>&yen;' + Number(data.total_amount).toFixed(2) + '</td>';
    html += '<td>' + data.total_count + LanguageManager.trans('today_work.paid_count_unit') + '</td></tr>';
    html += '</tbody></table></div></div>';
    return html;
}

function renderUnpaidTab(data) {
    if (!data.items || data.items.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.unpaid_no_data') + '</div>';
    }
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr>';
    html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.phone') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.total') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.paid') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.unpaid_outstanding') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.invoice_no') + '</th>';
    html += '</tr></thead><tbody>';
    data.items.forEach(function(item) {
        var statusClass = item.payment_status === 'unpaid' ? 'text-danger' : 'text-warning';
        html += '<tr>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.patient_phone) + '</td>';
        html += '<td>&yen;' + Number(item.total_amount).toFixed(2) + '</td>';
        html += '<td>&yen;' + Number(item.paid_amount).toFixed(2) + '</td>';
        html += '<td class="' + statusClass + '">&yen;' + Number(item.outstanding_amount).toFixed(2) + '</td>';
        html += '<td>' + _escHtml(item.invoice_no) + '</td>';
        html += '</tr>';
    });
    html += '<tr style="font-weight:600;background:#f8f9fa;"><td colspan="4">' + LanguageManager.trans('today_work.billing_total') + '</td>';
    html += '<td class="text-danger">&yen;' + Number(data.total_outstanding).toFixed(2) + '</td>';
    html += '<td>' + data.total_count + LanguageManager.trans('today_work.paid_count_unit') + '</td></tr>';
    html += '</tbody></table></div></div>';
    return html;
}

function renderLabCasesTab(data) {
    if (!data || data.length === 0) {
        return '<div class="tw-tab-empty">' + LanguageManager.trans('today_work.lab_cases_no_data') + '</div>';
    }
    var statusColors = {
        'pending': 'label-default', 'sent': 'label-info', 'in_production': 'label-primary',
        'returned': 'label-success', 'try_in': 'label-warning', 'completed': 'label-success', 'rework': 'label-danger'
    };
    var html = '<div class="portlet light bordered"><div class="portlet-body"><table class="table tw-info-table">';
    html += '<thead><tr>';
    html += '<th>' + LanguageManager.trans('today_work.lab_case_no') + '</th>';
    html += '<th>' + LanguageManager.trans('common.patient') + '</th>';
    html += '<th>' + LanguageManager.trans('common.doctor') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.lab_prosthesis_type') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.lab_material') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.lab_name') + '</th>';
    html += '<th>' + LanguageManager.trans('common.status') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.lab_expected_date') + '</th>';
    html += '<th>' + LanguageManager.trans('today_work.lab_actual_date') + '</th>';
    html += '</tr></thead><tbody>';
    data.forEach(function(item) {
        var badgeClass = statusColors[item.status] || 'label-default';
        html += '<tr>';
        html += '<td>' + _escHtml(item.lab_case_no) + '</td>';
        html += '<td><span class="clickable-name" onclick="openPatientDrawer(' + item.patient_id + ')">' + _escHtml(item.patient_name) + '</span></td>';
        html += '<td>' + _escHtml(item.doctor_name) + '</td>';
        html += '<td>' + _escHtml(item.prosthesis_type) + '</td>';
        html += '<td>' + _escHtml(item.material) + '</td>';
        html += '<td>' + _escHtml(item.lab_name) + '</td>';
        html += '<td><span class="label ' + badgeClass + '">' + LanguageManager.trans('today_work.lab_status_' + item.status) + '</span></td>';
        html += '<td>' + _escHtml(item.expected_return_date || '-') + '</td>';
        html += '<td>' + _escHtml(item.actual_return_date || '-') + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table></div></div>';
    return html;
}

// ── Utility ─────────────────────────────────────────

function _escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function loadTabCounts() {
    var url = window.twTabUrls ? (window.twTabUrls['tab-counts'] || '/today-work/tab-counts') : '/today-work/tab-counts';
    var params = {};
    var dateVal = $('#tw-date-filter').val();
    if (dateVal) params.date = dateVal;
    $.getJSON(url, params, function(data) {
        if (data.followups) $('#badge-followups').text(data.followups);
        if (data.tomorrow) $('#badge-tomorrow').text(data.tomorrow);
        if (data.week_missed) $('#badge-week-missed').text(data.week_missed);
        if (data.birthdays) $('#badge-birthdays').text(data.birthdays);
        if (data.paid) $('#badge-paid').text(data.paid);
        if (data.unpaid) $('#badge-unpaid').text(data.unpaid);
        if (data.lab_cases) $('#badge-lab-cases').text(data.lab_cases);
    });
}
