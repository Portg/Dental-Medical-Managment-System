<div class="modal fade modal-form modal-form-lg" id="edit-lab-case-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.edit_lab_case') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="edit-lab-case-form" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.prosthesis_type') }}</label>
                                <select id="edit_prosthesis_type" name="prosthesis_type" class="form-control">
                                    @foreach(\App\LabCase::PROSTHESIS_TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ __('lab_cases.type_' . $key) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.material') }}</label>
                                <select id="edit_material" name="material" class="form-control">
                                    <option value="">--</option>
                                    @foreach(\App\LabCase::MATERIALS as $key => $label)
                                        <option value="{{ $key }}">{{ __('lab_cases.material_' . $key) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.color_shade') }}</label>
                                <input type="text" id="edit_color_shade" name="color_shade" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.teeth_positions') }}</label>
                                <input type="text" id="edit_teeth_positions" name="teeth_positions" class="form-control" placeholder="11, 12, 21">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.expected_return_date') }}</label>
                                <input type="date" id="edit_expected_return_date" name="expected_return_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.quality_rating') }}</label>
                                <select id="edit_quality_rating" name="quality_rating" class="form-control">
                                    <option value="">--</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.lab_fee') }}</label>
                                <input type="number" id="edit_lab_fee" name="lab_fee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.patient_charge') }}</label>
                                <input type="number" id="edit_patient_charge" name="patient_charge" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.special_requirements') }}</label>
                                <textarea id="edit_special_requirements" name="special_requirements" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.notes') }}</label>
                                <textarea id="edit_notes" name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-update" onclick="updateLabCase()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
