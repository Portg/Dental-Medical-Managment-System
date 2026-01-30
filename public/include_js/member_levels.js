/**
 * Member Levels Management JavaScript
 */

$(document).ready(function() {
    loadLevelsTable();
});

function loadLevelsTable() {
    dataTable = $(getTableSelector()).DataTable({
        processing: true,
        serverSide: true,
        ajax: '/member-levels',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'colorBadge', name: 'colorBadge'},
            {data: 'code', name: 'code'},
            {data: 'discountDisplay', name: 'discountDisplay'},
            {data: 'min_consumption', name: 'min_consumption'},
            {data: 'points_rate', name: 'points_rate'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[0, 'asc']],
        language: LanguageManager.getDataTableLang()
    });

    setupEmptyStateHandler();
}

function addLevel() {
    $('#levelForm')[0].reset();
    $('#levelForm .alert').hide();
    $('#level_color').val('#999999');
    $('#level_discount_rate').val(100);
    $('#level_points_rate').val(1);
    $('#level_sort_order').val(0);
    $('#level_is_active').val(1);
    $('#levelModal').modal('show');
}

function saveLevel() {
    var formData = new FormData($('#levelForm')[0]);

    $.ajax({
        url: '/member-levels',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#levelModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                dataTable.ajax.reload();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON.errors;
            var errorList = '';
            $.each(errors, function(key, value) {
                errorList += '<li>' + value[0] + '</li>';
            });
            $('#levelForm .alert ul').html(errorList);
            $('#levelForm .alert').show();
        }
    });
}

function editLevel(id) {
    $.ajax({
        url: '/member-levels/' + id + '/edit',
        type: 'GET',
        dataType: 'json',
        success: function(level) {
            $('#edit_level_id').val(level.id);
            $('#edit_level_name').val(level.name);
            $('#edit_level_code').val(level.code);
            $('#edit_level_color').val(level.color);
            $('#edit_level_discount_rate').val(level.discount_rate);
            $('#edit_level_min_consumption').val(level.min_consumption);
            $('#edit_level_points_rate').val(level.points_rate);
            $('#edit_level_sort_order').val(level.sort_order);
            $('#edit_level_is_active').val(level.is_active ? 1 : 0);
            $('#edit_level_benefits').val(level.benefits);
            $('#editLevelForm .alert').hide();
            $('#editLevelModal').modal('show');
        }
    });
}

function updateLevel() {
    var id = $('#edit_level_id').val();
    var formData = new FormData($('#editLevelForm')[0]);
    formData.append('_method', 'PUT');

    $.ajax({
        url: '/member-levels/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#editLevelModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                dataTable.ajax.reload();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON.errors;
            var errorList = '';
            $.each(errors, function(key, value) {
                errorList += '<li>' + value[0] + '</li>';
            });
            $('#editLevelForm .alert ul').html(errorList);
            $('#editLevelForm .alert').show();
        }
    });
}

function deleteLevel(id) {
    swal({
        title: LanguageManager.trans('messages.confirm_delete'),
        text: LanguageManager.trans('members.confirm_delete_level'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $.ajax({
                url: '/member-levels/' + id,
                type: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: response.message,
                            type: 'success'
                        });
                        dataTable.ajax.reload();
                    } else {
                        swal({
                            title: LanguageManager.trans('messages.error'),
                            text: response.message,
                            type: 'error'
                        });
                    }
                }
            });
        }
    });
}
