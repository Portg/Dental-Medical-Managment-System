<div class="modal fade" id="create-lab-case-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"></button>
                <h4 class="modal-title">{{ __('lab_cases.create_lab_case') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="create-lab-case-form" autocomplete="off">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.patient') }} *</label>
                                <select id="create_patient_id" name="patient_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.doctor') }} *</label>
                                <select id="create_doctor_id" name="doctor_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.lab') }} *</label>
                                <select name="lab_id" class="form-control">
                                    <option value="">{{ __('lab_cases.select_lab') }}</option>
                                    @foreach($labs as $lab)
                                        <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.prosthesis_type') }} *</label>
                                <select name="prosthesis_type" class="form-control">
                                    <option value="">--</option>
                                    @foreach(\App\LabCase::PROSTHESIS_TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ __('lab_cases.type_' . $key) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.material') }}</label>
                                <select name="material" class="form-control">
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
                                <input type="text" name="color_shade" class="form-control" placeholder="A2">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.teeth_positions') }}</label>
                                <input type="text" name="teeth_positions" class="form-control" placeholder="11, 12, 21">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.expected_return_date') }}</label>
                                <input type="date" name="expected_return_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.lab_fee') }}</label>
                                <input type="number" name="lab_fee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.patient_charge') }}</label>
                                <input type="number" name="patient_charge" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.special_requirements') }}</label>
                                <textarea name="special_requirements" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="btn-create" onclick="saveLabCase()">{{ __('common.save_changes') }}</button>
                <button class="btn dark btn-outline" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
