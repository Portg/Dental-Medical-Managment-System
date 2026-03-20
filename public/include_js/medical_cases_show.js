$(document).ready(function() {
    var versionLoaded = false, amendmentsLoaded = false;

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');

        if (target === '#version_history_tab' && !versionLoaded) {
            versionLoaded = true;
            loadVersionHistory();
        }
        if (target === '#amendments_tab' && !amendmentsLoaded) {
            amendmentsLoaded = true;
            loadAmendments();
        }
    });
});

function loadVersionHistory() {
    $.getJSON('/medical-cases/' + global_case_id + '/version-history', function(res) {
        if (!res.status || !res.data.length) {
            $('#version_history_content').html('<p class="text-muted">' + LanguageManager.trans('common.no_data_found') + '</p>');
            return;
        }
        var html = '<table class="table table-striped table-bordered"><thead><tr>'
            + '<th>' + LanguageManager.trans('common.time') + '</th>'
            + '<th>' + LanguageManager.trans('common.operator') + '</th>'
            + '<th>' + LanguageManager.trans('common.action') + '</th>'
            + '<th>' + LanguageManager.trans('medical_cases.modification_reason') + '</th>'
            + '</tr></thead><tbody>';
        $.each(res.data, function(i, audit) {
            var userName = audit.user ? audit.user.full_name : '-';
            var reason = (audit.new_values && audit.new_values.modification_reason) || '-';
            html += '<tr><td>' + audit.created_at + '</td><td>' + userName
                + '</td><td>' + LanguageManager.trans('common.audit_event_' + audit.event)
                + '</td><td>' + reason + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#version_history_content').html(html);
    });
}

function loadAmendments() {
    $.getJSON('/medical-cases/' + global_case_id + '/amendments', function(res) {
        if (!res.status || !res.data.length) {
            $('#amendments_content').html('<p class="text-muted">' + LanguageManager.trans('common.no_data_found') + '</p>');
            return;
        }
        var html = '<table class="table table-striped table-bordered"><thead><tr>'
            + '<th>' + LanguageManager.trans('common.time') + '</th>'
            + '<th>' + LanguageManager.trans('medical_cases.amendment_requested_by') + '</th>'
            + '<th>' + LanguageManager.trans('medical_cases.amendment_reason') + '</th>'
            + '<th>' + LanguageManager.trans('medical_cases.amendment_status') + '</th>'
            + '<th>' + LanguageManager.trans('common.actions') + '</th>'
            + '</tr></thead><tbody>';
        $.each(res.data, function(i, a) {
            var requester = a.requested_by_user ? a.requested_by_user.full_name : '-';
            var statusLabel = '';
            if (a.status === 'pending') statusLabel = '<span class="label label-warning">' + LanguageManager.trans('medical_cases.amendment_pending') + '</span>';
            else if (a.status === 'approved') statusLabel = '<span class="label label-success">' + LanguageManager.trans('medical_cases.amendment_approved') + '</span>';
            else statusLabel = '<span class="label label-danger">' + LanguageManager.trans('medical_cases.amendment_rejected') + '</span>';

            var actions = '-';
            if (a.status === 'pending' && canApproveAmendment) {
                actions = '<button class="btn btn-xs btn-success" onclick="approveAmendment(' + a.id + ')">' + LanguageManager.trans('medical_cases.approve_amendment') + '</button> '
                    + '<button class="btn btn-xs btn-danger" onclick="rejectAmendment(' + a.id + ')">' + LanguageManager.trans('medical_cases.reject_amendment') + '</button>';
            } else if (a.status !== 'pending' && a.review_notes) {
                actions = a.review_notes;
            }

            html += '<tr><td>' + a.created_at + '</td><td>' + requester
                + '</td><td>' + a.amendment_reason
                + '</td><td>' + statusLabel
                + '</td><td>' + actions + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#amendments_content').html(html);
    });
}

function approveAmendment(id) {
    var notes = prompt(LanguageManager.trans('medical_cases.amendment_review_notes'));
    if (notes === null) return;
    $.post('/medical-case-amendments/' + id + '/approve', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        review_notes: notes
    }, function(res) {
        swal(res.message, '', res.status ? 'success' : 'error');
        if (res.status) { loadAmendments(); }
    });
}

function rejectAmendment(id) {
    var notes = prompt(LanguageManager.trans('medical_cases.reject_reason_required'));
    if (!notes || notes.length < 5) {
        swal(LanguageManager.trans('medical_cases.reject_reason_required'), '', 'warning');
        return;
    }
    $.post('/medical-case-amendments/' + id + '/reject', {
        _token: $('meta[name="csrf-token"]').attr('content'),
        review_notes: notes
    }, function(res) {
        swal(res.message, '', res.status ? 'success' : 'error');
        if (res.status) { loadAmendments(); }
    });
}
