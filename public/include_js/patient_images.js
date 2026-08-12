$(document).ready(function() {
    loadImagesTable();

    // select2 默认 width:'resolve' —— 它去量元素的实际宽度来定容器宽度。这里的
    // .select2 长在 #imageModal 里，document.ready 时弹窗还是 display:none，量出来
    // 是 0，容器就渲染成一条看不见的缝：页面上只剩「患者」这个标签，底下什么都没有。
    // 必须显式给 width，别删。
    // dropdownParent 指到弹窗上，否则 BS3 的 modal 会抢焦点，select2 的搜索框打不了字。
    $('#patient_id').select2({
        width: '100%',
        dropdownParent: $('#imageModal'),
        placeholder: LanguageManager.trans('patient_images.select_patient'),
        allowClear: true
    });
    // 必须限定 select：select2 生成的包装 <span> 自己也带 .select2 类，
    // 写成 $('.select2') 会把上面刚生成的容器再初始化一遍，页面上就多出一个
    // 高 1px 的空壳盖在真控件上 —— 表现为「患者」下面一个空框，选了人也不显示。
    $('select.select2').not('#patient_id').select2({ width: '100%' });

    $('#image_file').on('change', handleImageFileSelection);
    $('#btn_change_file').on('click', function () {
        $('#image_file').val('').trigger('change');
    });
});

function loadImagesTable() {
    dataTable = $(getTableSelector()).DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        scrollX: true,
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
        language: LanguageManager.getDataTableLang(),
        initComplete: function() {
            if (typeof syncPatientImagesLayoutState === 'function') {
                syncPatientImagesLayoutState();
            }
        },
        drawCallback: function() {
            if (typeof syncPatientImagesLayoutState === 'function') {
                syncPatientImagesLayoutState();
            }
        }
    });

    setupEmptyStateHandler();
}

function addImage() {
    resetForm();
    $('#imageModalLabel').text(LanguageManager.trans('patient_images.add_image'));
    $('#imageModal').modal('show');
}

function resetForm() {
    $('#imageForm')[0].reset();
    $('#image_id').val('');
    $('#patient_id').val('').trigger('change');
    setImagePreviewState(null, '');
    $('#imageForm .alert-danger').hide();
}

// 上传区只有两态：没选文件 → 拖拽框；选了 → 缩略图行（拖拽框由 CSS 相邻选择器隐藏）。
// src 为空但有 fileName 的中间态是「已选中，缩略图还在读」，此时留占位图标。
function setImagePreviewState(src, fileName) {
    var hasImage = !!src;
    $('#preview_image').attr('src', src || '').toggle(hasImage);
    $('#image_preview_placeholder').toggle(!hasImage);

    $('#selected_file_name').text(fileName || LanguageManager.trans('patient_images.selected_file'));
    $('#selected_file_meta').toggleClass('is-visible', !!fileName);
}

function handleImageFileSelection() {
    var file = $('#image_file')[0].files[0];

    if (!file) {
        setImagePreviewState('', '');
        return;
    }

    setImagePreviewState('', file.name);

    if (file.type && file.type.indexOf('image/') === 0) {
        var reader = new FileReader();
        reader.onload = function(e) {
            setImagePreviewState(e.target.result, file.name);
        };
        reader.readAsDataURL(file);
    }
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

            $('#image_id').val(response.id);
            $('#patient_id').val(response.patient_id).trigger('change');
            $('#title').val(response.title);
            $('#image_type').val(response.image_type);
            $('#image_date').val(response.image_date);
            $('#tooth_number').val(response.tooth_number);
            $('#description').val(response.description);

            if (response.file_path) {
                var existingFileName = response.file_name || response.title || LanguageManager.trans('patient_images.selected_file');
                setImagePreviewState('/' + response.file_path, existingFileName);
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
