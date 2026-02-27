/**
 * Member Settings Page JavaScript
 */

function saveSettings() {
    var formData = new FormData($('#settingsForm')[0]);
    formData.append('_method', 'PUT');

    $.ajax({
        url: '/member-settings',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
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
        error: function(xhr) {
            swal({
                title: LanguageManager.trans('messages.error'),
                text: xhr.responseJSON ? xhr.responseJSON.message : 'Error',
                type: 'error'
            });
        }
    });
}
