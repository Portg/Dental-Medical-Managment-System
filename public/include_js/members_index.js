/**
 * Members Index Page — page script
 * PHP values bridged via window.MembersIndexConfig (set in Blade).
 */

function createRecord() {
    addMember();
}

// Auto-open registration modal when patient_id is in URL (from patient detail page)
$(document).ready(function () {
    var urlParams = new URLSearchParams(window.location.search);
    var patientId = urlParams.get('patient_id');
    if (patientId) {
        setTimeout(function () {
            addMember();
            $('#patient_id').val(patientId).trigger('change');
        }, 500);
    }
});
