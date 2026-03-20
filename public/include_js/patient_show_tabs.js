// Prescriptions tab lazy loading
var prescriptionsInited = false;
$('a[href="#prescriptions_tab"]').on('shown.bs.tab', function() {
    if (!prescriptionsInited) {
        prescriptionsInited = true;
        loadPatientPrescriptions();
    }
});

// Lab Cases tab lazy loading
var labCasesInited = false;
$('a[href="#lab_cases_tab"]').on('shown.bs.tab', function() {
    if (!labCasesInited) {
        labCasesInited = true;
        loadPatientLabCases();
    }
});

// Reveal / Hide PII toggle
var piiRevealed = false;
var piiMaskedValues = {};
// Store initial masked values
$('.pii-field').each(function() {
    var field = $(this).data('field');
    piiMaskedValues[field] = $(this).text();
});

$('#revealPiiBtn').click(function() {
    var btn = $(this);

    if (piiRevealed) {
        // Hide: restore masked values
        $('.pii-field').each(function() {
            var field = $(this).data('field');
            if (piiMaskedValues[field] !== undefined) {
                $(this).text(piiMaskedValues[field]);
            }
        });
        piiRevealed = false;
        btn.html('<i class="fa fa-eye"></i> ' + LanguageManager.trans('data_security.reveal_sensitive'));
        return;
    }

    // Reveal: fetch real data
    btn.prop('disabled', true);
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
    $.post('/patients/' + global_patient_id + '/reveal-pii', {_token: CSRF_TOKEN}, function(resp) {
        $('.pii-field').each(function() {
            var field = $(this).data('field');
            if (resp[field] !== undefined && resp[field] !== null) {
                $(this).text(resp[field]);
            }
        });
        piiRevealed = true;
        btn.prop('disabled', false);
        btn.html('<i class="fa fa-eye-slash"></i> ' + LanguageManager.trans('data_security.hide_sensitive'));
    }).fail(function() {
        btn.prop('disabled', false);
        alert(LanguageManager.trans('data_security.reveal_failed'));
    });
});

// Left panel: auto-save tags and group changes
$(document).on('change', 'input[name="panel_tags[]"], input[name="panel_group"]', function() {
    var tagIds = [];
    $('input[name="panel_tags[]"]:checked').each(function() {
        tagIds.push($(this).val());
    });
    var group = $('input[name="panel_group"]:checked').val();
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    $.post('/patients/' + global_patient_id + '/quick-info', {
        _token: CSRF_TOKEN,
        tag_ids: tagIds,
        patient_group: group
    }, function(resp) {
        if (resp.status) {
            toastr.success(resp.message);
        }
    });
});
