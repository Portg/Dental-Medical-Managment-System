<style>
    #imageModal .modal-dialog {
        width: 960px;
        max-width: 96%;
    }

    #imageModal .modal-body {
        padding: 24px;
    }

    .patient-image-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .patient-image-section {
        border: 1px solid #e6edf1;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
    }

    .patient-image-section__header {
        padding: 16px 20px;
        border-bottom: 1px solid #edf3f6;
        background: linear-gradient(180deg, #fbfefe 0%, #f5fafb 100%);
    }

    .patient-image-section__title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }

    .patient-image-section__hint {
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.6;
        color: #6b7280;
    }

    .patient-image-section__body {
        padding: 20px;
    }

    .patient-image-upload {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(260px, 0.8fr);
        gap: 20px;
        align-items: stretch;
    }

    .patient-image-dropzone {
        position: relative;
        display: flex;
        min-height: 220px;
        border: 2px dashed #9fd5da;
        border-radius: 18px;
        background: linear-gradient(180deg, #fbfefe 0%, #f3fbfc 100%);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .patient-image-dropzone:hover {
        border-color: #48aab3;
        box-shadow: 0 8px 24px rgba(0, 131, 143, 0.08);
        transform: translateY(-1px);
    }

    .patient-image-dropzone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .patient-image-dropzone__content {
        position: relative;
        z-index: 1;
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 28px;
        color: #4b5563;
    }

    .patient-image-dropzone__icon {
        width: 72px;
        height: 72px;
        margin-bottom: 16px;
        border-radius: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 131, 143, 0.12);
        color: #00838f;
        font-size: 28px;
    }

    .patient-image-dropzone__title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .patient-image-dropzone__hint {
        max-width: 360px;
        font-size: 14px;
        line-height: 1.7;
        color: #6b7280;
    }

    .patient-image-file-meta {
        margin-top: 16px;
        display: none;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #d9e9ed;
        font-size: 13px;
        color: #374151;
    }

    .patient-image-file-meta.is-visible {
        display: inline-flex;
    }

    .patient-image-file-meta i {
        color: #00838f;
    }

    .patient-image-preview-card {
        border-radius: 18px;
        background: #f8fbfc;
        border: 1px solid #e5eef2;
        padding: 16px;
        display: flex;
        flex-direction: column;
        min-height: 220px;
    }

    .patient-image-preview-card__label {
        font-size: 13px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 12px;
    }

    .patient-image-preview-card__frame {
        flex: 1;
        border-radius: 14px;
        background: #fff;
        border: 1px solid #e3edf2;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        min-height: 150px;
    }

    .patient-image-preview-card__placeholder {
        text-align: center;
        color: #9ca3af;
        line-height: 1.7;
        padding: 20px;
    }

    .patient-image-preview-card__placeholder i {
        display: block;
        font-size: 28px;
        margin-bottom: 10px;
    }

    .patient-image-preview-card img {
        max-width: 100%;
        max-height: 220px;
        object-fit: contain;
    }

    .patient-image-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 20px;
    }

    .patient-image-field {
        min-width: 0;
    }

    .patient-image-field--full {
        grid-column: 1 / -1;
    }

    .patient-image-field label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .patient-image-field .text-danger {
        line-height: 1;
    }

    .patient-image-field .form-control,
    .patient-image-field .select2-container .select2-selection--single {
        min-height: 42px;
        border-radius: 10px;
    }

    .patient-image-field textarea.form-control {
        min-height: 108px;
        resize: vertical;
    }

    .patient-image-field-help {
        margin-top: 8px;
        font-size: 12px;
        line-height: 1.6;
        color: #8a97a6;
    }

    .patient-image-aux-card {
        border-radius: 14px;
        background: #fafcfd;
        border: 1px solid #edf3f6;
        padding: 16px;
    }

    @media (max-width: 991px) {
        .patient-image-upload,
        .patient-image-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        #imageModal .modal-body {
            padding: 16px;
        }

        .patient-image-section__header,
        .patient-image-section__body {
            padding: 16px;
        }

        .patient-image-dropzone {
            min-height: 190px;
        }
    }
</style>
<div class="modal fade modal-form modal-form-lg" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="imageModalLabel">{{ __('patient_images.add_image') }}</h4>
            </div>
            <div class="modal-body">
                <form id="imageForm" class="patient-image-form" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="image_id" id="image_id">

                    <section class="patient-image-section">
                        <div class="patient-image-section__header">
                            <h5 class="patient-image-section__title">{{ __('patient_images.upload_priority_title') }}</h5>
                            <div class="patient-image-section__hint">{{ __('patient_images.upload_priority_hint') }}</div>
                        </div>
                        <div class="patient-image-section__body">
                            <div class="patient-image-upload">
                                <label class="patient-image-dropzone">
                                    <input type="file" name="image_file" id="image_file" accept="image/*">
                                    <div class="patient-image-dropzone__content">
                                        <div class="patient-image-dropzone__icon">
                                            <i class="fa fa-cloud-upload"></i>
                                        </div>
                                        <div class="patient-image-dropzone__title">{{ __('patient_images.image_file') }} <span class="text-danger" id="file_required">*</span></div>
                                        <div class="patient-image-dropzone__hint">{{ __('patient_images.file_hint') }}</div>
                                        <div class="patient-image-file-meta" id="selected_file_meta">
                                            <i class="fa fa-file-image-o"></i>
                                            <span id="selected_file_name">{{ __('patient_images.selected_file') }}</span>
                                        </div>
                                    </div>
                                </label>

                                <div class="patient-image-preview-card">
                                    <div class="patient-image-preview-card__label">{{ __('patient_images.view_image') }}</div>
                                    <div class="patient-image-preview-card__frame">
                                        <div class="patient-image-preview-card__placeholder" id="image_preview_placeholder">
                                            <i class="fa fa-picture-o"></i>
                                            <div>{{ __('patient_images.upload_priority_hint') }}</div>
                                        </div>
                                        <div id="current_image_preview" style="display:none;">
                                            <img id="preview_image" src="" alt="preview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="patient-image-section">
                        <div class="patient-image-section__header">
                            <h5 class="patient-image-section__title">{{ __('patient_images.basic_info') }}</h5>
                            <div class="patient-image-section__hint">{{ __('patient_images.basic_info_hint') }}</div>
                        </div>
                        <div class="patient-image-section__body">
                            <div class="patient-image-grid">
                                <div class="patient-image-field">
                                    <label for="patient_id">{{ __('patient_images.patient') }} <span class="text-danger">*</span></label>
                                    <select name="patient_id" id="patient_id" class="form-control select2">
                                        <option value="">{{ __('common.select') }}</option>
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_no }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="patient-image-field">
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

                                <div class="patient-image-field patient-image-field--full">
                                    <label for="title">{{ __('patient_images.title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="{{ __('patient_images.title_placeholder') }}">
                                </div>

                                <div class="patient-image-field">
                                    <label for="image_date">{{ __('patient_images.image_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="image_date" id="image_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="patient-image-section">
                        <div class="patient-image-section__header">
                            <h5 class="patient-image-section__title">{{ __('patient_images.auxiliary_info') }}</h5>
                            <div class="patient-image-section__hint">{{ __('patient_images.auxiliary_info_hint') }}</div>
                        </div>
                        <div class="patient-image-section__body">
                            <div class="patient-image-grid">
                                <div class="patient-image-field">
                                    <div class="patient-image-aux-card">
                                        <label for="tooth_number">{{ __('patient_images.tooth_number') }}</label>
                                        <input type="text" name="tooth_number" id="tooth_number" class="form-control" placeholder="{{ __('patient_images.tooth_number_placeholder') }}">
                                        <div class="patient-image-field-help">{{ __('patient_images.auxiliary_info_hint') }}</div>
                                    </div>
                                </div>

                                <div class="patient-image-field">
                                    <div class="patient-image-aux-card">
                                        <label for="description">{{ __('patient_images.description') }}</label>
                                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('patient_images.description_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="saveImage()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
