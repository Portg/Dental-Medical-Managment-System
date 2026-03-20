/**
 * Member Levels / Settings Page — page script
 * PHP values bridged via window.MemberLevelsIndexConfig (set in Blade).
 */

var dataTable = null;

function createRecord() {
    addLevel();
}

function getTableSelector() {
    return '#levels_table';
}

function setupEmptyStateHandler() {
    // No-op: integrated into tab layout
}

/**
 * Save settings from any tab form.
 */
function saveSettingsTab(formId) {
    var formData = new FormData($('#' + formId)[0]);
    formData.append('_method', 'PUT');

    $.ajax({
        url: '/member-settings',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.status) {
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function (xhr) {
            swal({
                title: LanguageManager.trans('messages.error'),
                text: xhr.responseJSON ? xhr.responseJSON.message : 'Error',
                type: 'error'
            });
        }
    });
}

// Switch to tab via URL hash
$(document).ready(function () {
    if (window.location.hash) {
        var tab = window.location.hash;
        $('.nav-tabs a[href="' + tab + '"]').tab('show');
    }
});
