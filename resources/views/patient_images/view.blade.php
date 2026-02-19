<div class="modal fade modal-form modal-form-lg" id="viewImageModal" tabindex="-1" role="dialog" aria-labelledby="viewImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="viewImageModalLabel">{{ __('patient_images.view_image') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="text-center">
                            <img id="view_image_src" src="" style="max-width:100%; max-height:500px;" class="img-responsive">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">{{ __('patient_images.image_details') }}</h4>
                            </div>
                            <div class="panel-body">
                                <p><strong>{{ __('patient_images.image_no') }}:</strong><br>
                                    <span id="view_image_no"></span>
                                </p>
                                <p><strong>{{ __('patient_images.title') }}:</strong><br>
                                    <span id="view_title"></span>
                                </p>
                                <p><strong>{{ __('patient_images.image_type') }}:</strong><br>
                                    <span id="view_image_type"></span>
                                </p>
                                <p><strong>{{ __('patient_images.image_date') }}:</strong><br>
                                    <span id="view_image_date"></span>
                                </p>
                                <p><strong>{{ __('patient_images.tooth_number') }}:</strong><br>
                                    <span id="view_tooth_number"></span>
                                </p>
                                <p><strong>{{ __('patient_images.file_name') }}:</strong><br>
                                    <span id="view_file_name"></span>
                                </p>
                                <p><strong>{{ __('patient_images.description') }}:</strong><br>
                                    <span id="view_description"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <a id="download_image_btn" href="" download class="btn btn-primary">
                    {{ __('patient_images.download') }}
                </a>
            </div>
        </div>
    </div>
</div>
