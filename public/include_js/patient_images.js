$(document).ready(function() {
    loadImagesTable();

    // Initialize select2
    $('.select2').select2();
});

function loadImagesTable() {
    dataTable = $(getTableSelector()).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-images',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'image_no', name: 'image_no'},
            {data: 'title', name: 'title'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'image_date', name: 'image_date'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[5, 'desc']],
        language: LanguageManager.getDataTableLang()
    });

    setupEmptyStateHandler();
}

function addImage() {
    resetForm();
    $('#imageModalLabel').text(LanguageManager.trans('patient_images.add_image'));
    $('#file_required').show();
    $('#imageModal').modal('show');
}

function resetForm() {
    $('#imageForm')[0].reset();
    $('#image_id').val('');
    $('#patient_id').val('').trigger('change');
    $('#current_image_preview').hide();
    $('#imageForm .alert-danger').hide();
}

function saveImage() {
    var formData = new FormData($('#imageForm')[0]);
    var imageId = $('#image_id').val();
    var url = imageId ? '/patient-images/' + imageId : '/patient-images';
    var method = imageId ? 'POST' : 'POST';

    if (imageId) {
        formData.append('_method', 'PUT');
    }

    $('.loading').show();

    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#imageModal').modal('hide');
                dataTable.ajax.reload();
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
            $('.loading').hide();
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#imageForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#imageForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function editImage(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-images/' + id + '/edit',
        type: 'GET',
        success: function(response) {
            $('.loading').hide();
            resetForm();
            $('#imageModalLabel').text(LanguageManager.trans('patient_images.edit_image'));
            $('#file_required').hide();

            $('#image_id').val(response.id);
            $('#patient_id').val(response.patient_id).trigger('change');
            $('#title').val(response.title);
            $('#image_type').val(response.image_type);
            $('#image_date').val(response.image_date);
            $('#tooth_number').val(response.tooth_number);
            $('#description').val(response.description);

            if (response.file_path) {
                $('#preview_image').attr('src', '/' + response.file_path);
                $('#current_image_preview').show();
            }

            $('#imageModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function viewImage(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-images/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_image_no').text(response.image_no);
            $('#view_title').text(response.title);
            $('#view_image_type').text(LanguageManager.trans('patient_images.type_' + response.image_type.toLowerCase().replace('-', '_')));
            $('#view_image_date').text(response.image_date);
            $('#view_tooth_number').text(response.tooth_number || '-');
            $('#view_file_name').text(response.file_name);
            $('#view_description').text(response.description || '-');

            $('#view_image_src').attr('src', '/' + response.file_path);
            $('#download_image_btn').attr('href', '/' + response.file_path);

            $('#viewImageModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function deleteImage(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('patient_images.delete_confirmation'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/patient-images/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        dataTable.ajax.reload();
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
                error: function() {
                    $('.loading').hide();
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: LanguageManager.trans('messages.error_occurred'),
                        type: 'error'
                    });
                }
            });
        }
    });
}
