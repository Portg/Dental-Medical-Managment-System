(function() {
    'use strict';

    var t = function(key) {
        return LanguageManager.trans('system_settings.' + key);
    };

    window.saveSettings = function(group) {
        var formMap = {
            'clinic':    '#clinicSettingsForm',
            'schedule':  '#scheduleSettingsForm',
            'member':    '#memberSettingsForm'
        };
        var formId = formMap[group] || '#clinicSettingsForm';
        var $form = $(formId);
        var data = $form.serialize();

        $.LoadingOverlay("show");
        $.ajax({
            url: '/system-settings/' + group,
            type: 'PUT',
            data: data,
            dataType: 'json',
            success: function(response) {
                $.LoadingOverlay("hide");
                if (response.status) {
                    toastr.success(response.message || t('saved'));
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                $.LoadingOverlay("hide");
                var json = xhr.responseJSON;
                toastr.error(json && json.message ? json.message : LanguageManager.trans('messages.error_occurred'));
            }
        });
    };

})();
