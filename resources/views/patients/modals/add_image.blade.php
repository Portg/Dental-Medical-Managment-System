<div class="modal fade modal-form modal-form-lg" id="addImageModal" tabindex="-1" role="dialog" aria-labelledby="addImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addImageModalLabel">{{ __('patient_images.add_image') }}</h4>
            </div>
            <div class="modal-body">
                <form id="patientImageForm" class="img-upload-form" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                    {{-- Upload Zone --}}
                    <div class="img-upload-zone" id="imageUploadZone">
                        <input type="file" name="image_file" id="image_file" accept="image/*" class="img-upload-input">
                        <div class="img-upload-placeholder" id="imageUploadPlaceholder">
                            <i class="fa fa-cloud-upload"></i>
                            <p class="img-upload-title">{{ __('patient_images.upload_priority_title') }}</p>
                            <p class="img-upload-hint">{{ __('patient_images.file_hint') }}</p>
                        </div>
                        <div class="img-upload-preview" id="imageUploadPreview" style="display:none">
                            <div class="img-preview-thumb">
                                <img id="imagePreviewImg" src="" alt="">
                            </div>
                            <div class="img-preview-info">
                                <span class="img-preview-name" id="imagePreviewName"></span>
                                <span class="img-preview-meta" id="imagePreviewMeta"></span>
                            </div>
                            <button type="button" class="img-preview-remove" id="imagePreviewRemove" title="{{ __('common.delete') }}">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Basic Info Section --}}
                    <div class="img-form-section">
                        <div class="img-section-label">{{ __('patient_images.basic_info') }}</div>
                        <div class="img-form-grid">
                            <div class="img-form-field img-form-field-full">
                                <label for="image_title">{{ __('patient_images.title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="image_title" class="form-control" placeholder="{{ __('patient_images.title_placeholder') }}">
                            </div>
                            <div class="img-form-field">
                                <label for="image_type">{{ __('patient_images.image_type') }} <span class="text-danger">*</span></label>
                                <select name="image_type" id="image_type" class="form-control">
                                    <option value="">{{ __('common.select') }}</option>
                                    <option value="X-Ray">{{ __('patient_images.type_x_ray') }}</option>
                                    <option value="CT">{{ __('patient_images.type_ct') }}</option>
                                    <option value="Intraoral">{{ __('patient_images.type_intraoral') }}</option>
                                    <option value="Extraoral">{{ __('patient_images.type_extraoral') }}</option>
                                    <option value="Other">{{ __('patient_images.type_other') }}</option>
                                </select>
                            </div>
                            <div class="img-form-field">
                                <label for="image_date">{{ __('patient_images.image_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="image_date" id="image_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Auxiliary Info Section --}}
                    <div class="img-form-section">
                        <div class="img-section-label">{{ __('patient_images.auxiliary_info') }} <span class="img-section-hint">{{ __('patient_images.auxiliary_info_hint') }}</span></div>
                        <div class="img-form-grid">
                            <div class="img-form-field">
                                <label for="tooth_number">{{ __('patient_images.tooth_number') }}</label>
                                <input type="text" name="tooth_number" id="tooth_number" class="form-control" placeholder="{{ __('patient_images.tooth_number_placeholder') }}">
                            </div>
                            <div class="img-form-field img-form-field-full">
                                <label for="image_description">{{ __('patient_images.description') }}</label>
                                <textarea name="description" id="image_description" class="form-control" rows="2" placeholder="{{ __('patient_images.description_placeholder') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary btn-save-image" onclick="savePatientImage()">
                    <i class="fa fa-check"></i> {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>
