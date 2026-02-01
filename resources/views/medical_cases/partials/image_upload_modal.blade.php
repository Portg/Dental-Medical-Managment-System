{{-- Image Upload Modal --}}
<div class="modal fade" id="image_upload_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.select_images') }}</h4>
            </div>
            <div class="modal-body">
                <form id="image-upload-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="patient_id" id="image_patient_id">
                    <div class="form-group">
                        <label>{{ __('patient_images.title') }} <span class="required">*</span></label>
                        <input type="text" name="title" id="image_title" class="form-control"
                               placeholder="{{ __('patient_images.title') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('patient_images.image_type') }} <span class="required">*</span></label>
                        <select name="image_type" id="image_type" class="form-control">
                            <option value="Intraoral">{{ __('patient_images.intraoral') }}</option>
                            <option value="X-Ray">{{ __('patient_images.xray') }}</option>
                            <option value="CT">CT</option>
                            <option value="Extraoral">{{ __('patient_images.extraoral') }}</option>
                            <option value="Other">{{ __('patient_images.other') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('patient_images.image_date') }} <span class="required">*</span></label>
                        <input type="date" name="image_date" id="image_date" class="form-control"
                               value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('patient_images.select_file') }} <span class="required">*</span></label>
                        <input type="file" name="image_file" id="image_file" class="form-control"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/bmp">
                        <p class="help-block">{{ __('patient_images.file_hint') }}</p>
                    </div>
                    <div class="form-group">
                        <label>{{ __('patient_images.description') }}</label>
                        <textarea name="description" id="image_description" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-upload-image" onclick="uploadImage()">
                    {{ __('common.upload') }}
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function uploadImage() {
    var patientId = $('#patient_id').val();
    if (!patientId) {
        toastr.error('{{ __("medical_cases.select_patient_hint") }}');
        return;
    }

    var title = $('#image_title').val();
    var imageFile = $('#image_file')[0].files[0];
    if (!title || !imageFile) {
        toastr.error('{{ __("messages.required_fields_missing") }}');
        return;
    }

    $('#image_patient_id').val(patientId);

    var formData = new FormData($('#image-upload-form')[0]);
    var $btn = $('#btn-upload-image');
    $btn.prop('disabled', true).text('{{ __("common.uploading") }}...');

    $.ajax({
        url: '{{ url("patient-images") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                toastr.success(response.message);

                // Add to related images list
                var images = JSON.parse($('#related_images').val() || '[]');
                var newImage = {
                    id: response.data ? response.data.id : Date.now(),
                    title: title,
                    file_path: response.data ? response.data.file_path : '',
                    image_type: $('#image_type').val()
                };
                images.push(newImage);
                $('#related_images').val(JSON.stringify(images));

                // Add preview
                addImagePreview(newImage);

                // Reset form and close
                $('#image-upload-form')[0].reset();
                $('#image_date').val('{{ date("Y-m-d") }}');
                $('#image_upload_modal').modal('hide');
            } else {
                toastr.error(response.message || '{{ __("messages.error_occurred") }}');
            }
        },
        error: function(xhr) {
            var msg = '{{ __("messages.error_occurred") }}';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                msg = Object.values(errors).map(function(e) { return e.join(', '); }).join('\n');
            }
            toastr.error(msg);
        },
        complete: function() {
            $btn.prop('disabled', false).text('{{ __("common.upload") }}');
        }
    });
}

function addImagePreview(image) {
    var src = image.file_path ? ('/' + image.file_path) : '';
    var html = '<div class="image-preview-item" data-id="' + image.id + '" ' +
               'style="position:relative; width:80px; height:80px; border:1px solid #e8e8e8; border-radius:4px; overflow:hidden;">';
    if (src) {
        html += '<img src="' + src + '" style="width:100%; height:100%; object-fit:cover;">';
    }
    html += '<div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.5); color:#fff; font-size:10px; padding:2px 4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">' +
            image.title + '</div>';
    html += '<span onclick="removeImage(' + image.id + ')" ' +
            'style="position:absolute; top:2px; right:4px; color:#fff; cursor:pointer; font-size:14px; text-shadow:0 0 2px rgba(0,0,0,0.5);">&times;</span>';
    html += '</div>';
    $('#auxiliary-image-preview').append(html);
}

function removeImage(imageId) {
    var images = JSON.parse($('#related_images').val() || '[]');
    images = images.filter(function(img) { return img.id != imageId; });
    $('#related_images').val(JSON.stringify(images));
    $('#auxiliary-image-preview .image-preview-item[data-id="' + imageId + '"]').remove();
}
</script>
