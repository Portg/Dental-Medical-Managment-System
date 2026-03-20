function callNextPatient() {
    $('#chair-modal').modal('show');
}

function doCallNext() {
    var chairId = $('#chair-id').val();

    $.post(window.DoctorQueueConfig.urls.callNext, {
        _token: $('meta[name="csrf-token"]').attr('content'),
        chair_id: chairId
    }, function(response) {
        if (response.status === 'success') {
            $('#chair-modal').modal('hide');
            toastr.success(response.message);
            location.reload();
        } else if (response.status === 'info') {
            toastr.info(response.message);
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

function completeTreatment(id) {
    $.post(window.DoctorQueueConfig.urls.waitingQueueBase + '/' + id + '/complete', {
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function(response) {
        if (response.status === 'success') {
            toastr.success(response.message);
            location.reload();
        } else {
            toastr.error(response.message);
        }
    }).fail(function(xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || LanguageManager.trans('common.error'));
    });
}

// Auto refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
