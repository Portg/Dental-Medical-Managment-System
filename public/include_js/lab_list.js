var dataTable;

$(function () {
    dataTable = $('#labs-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: labUrls.index
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'name', name: 'name'},
            {data: 'contact', name: 'contact'},
            {data: 'phone', name: 'phone'},
            {data: 'specialties', name: 'specialties'},
            {data: 'avg_turnaround_days', name: 'avg_turnaround_days'},
            {data: 'status_label', name: 'is_active', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function createLab() {
    $("#create-lab-form")[0].reset();
    $('.alert-danger').hide().find('ul').html('');
    $('#create-lab-modal').modal('show');
}

function saveLab() {
    $.LoadingOverlay("show");
    $('#btn-create-lab').attr('disabled', true).text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#create-lab-form').serialize(),
        url: labUrls.store,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#btn-create-lab').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            $('#create-lab-modal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-create-lab').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

function editLab(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'GET',
        url: labUrls.show + '/' + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#edit_lab_id').val(data.id);
            $('#edit_lab_name').val(data.name);
            $('#edit_lab_contact').val(data.contact);
            $('#edit_lab_phone').val(data.phone);
            $('#edit_lab_address').val(data.address);
            $('#edit_lab_specialties').val(data.specialties);
            $('#edit_lab_avg_turnaround_days').val(data.avg_turnaround_days);
            $('#edit_lab_is_active').prop('checked', data.is_active);
            $('.alert-danger').hide().find('ul').html('');
            $('#edit-lab-modal').modal('show');
        },
        error: function () {
            $.LoadingOverlay("hide");
        }
    });
}

function updateLab() {
    var id = $('#edit_lab_id').val();
    $.LoadingOverlay("show");
    $('#btn-update-lab').attr('disabled', true).text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'PUT',
        data: $('#edit-lab-form').serialize(),
        url: labUrls.update + '/' + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#btn-update-lab').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            $('#edit-lab-modal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-update-lab').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

function deleteLab(id) {
    swal({
        title: LanguageManager.trans('lab_cases.are_you_sure'),
        text: LanguageManager.trans('lab_cases.confirm_delete_lab'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('lab_cases.yes_delete_it'),
        cancelButtonText: LanguageManager.trans('lab_cases.cancel'),
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'DELETE',
            data: {_token: CSRF_TOKEN},
            url: labUrls.destroy + '/' + id,
            success: function (data) {
                $.LoadingOverlay("hide");
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
                if (data.status) {
                    dataTable.draw(false);
                }
            },
            error: function () {
                $.LoadingOverlay("hide");
            }
        });
    });
}
