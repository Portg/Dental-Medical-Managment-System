<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="imageModalLabel">{{ __('patient_images.add_image') }}</h4>
            </div>
            <div class="modal-body">
                <form id="imageForm" class="form-horizontal" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="image_id" id="image_id">

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.patient') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <select name="patient_id" id="patient_id" class="form-control select2">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">{{ $patient->surname }} {{ $patient->othername }} ({{ $patient->patient_no }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.title') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="title" id="title" class="form-control" placeholder="{{ __('patient_images.title_placeholder') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.image_type') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <select name="image_type" id="image_type" class="form-control">
                                <option value="">{{ __('common.select') }}</option>
                                <option value="X-Ray">{{ __('patient_images.type_x_ray') }}</option>
                                <option value="CT">{{ __('patient_images.type_ct') }}</option>
                                <option value="Intraoral">{{ __('patient_images.type_intraoral') }}</option>
                                <option value="Extraoral">{{ __('patient_images.type_extraoral') }}</option>
                                <option value="Other">{{ __('patient_images.type_other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.image_date') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="date" name="image_date" id="image_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.tooth_number') }}</label>
                        <div class="col-md-9">
                            <input type="text" name="tooth_number" id="tooth_number" class="form-control" placeholder="{{ __('patient_images.tooth_number_placeholder') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.image_file') }} <span class="text-danger" id="file_required">*</span></label>
                        <div class="col-md-9">
                            <input type="file" name="image_file" id="image_file" class="form-control" accept="image/*">
                            <small class="text-muted">{{ __('patient_images.file_hint') }}</small>
                            <div id="current_image_preview" style="display:none; margin-top:10px;">
                                <img id="preview_image" src="" style="max-width:200px; max-height:150px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_images.description') }}</label>
                        <div class="col-md-9">
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('patient_images.description_placeholder') }}"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="saveImage()">{{ __('common.save') }}</button>
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
