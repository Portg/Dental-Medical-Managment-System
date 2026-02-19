<div class="modal fade modal-form modal-form-lg" id="vital_sign_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="vital_sign_modal_title">{{ __('medical_cases.add_vital_sign') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="vital_sign_form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" name="vital_sign_id" id="vital_sign_id">
                    <input type="hidden" name="patient_id" id="vital_patient_id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.recorded_at') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="datetime-local" name="recorded_at" id="recorded_at" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary">{{ __('medical_cases.cardiovascular') }}</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.systolic') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="blood_pressure_systolic" id="blood_pressure_systolic" class="form-control" min="60" max="250" placeholder="120">
                                            <span class="input-group-addon">mmHg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.diastolic') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="blood_pressure_diastolic" id="blood_pressure_diastolic" class="form-control" min="40" max="150" placeholder="80">
                                            <span class="input-group-addon">mmHg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.heart_rate') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="heart_rate" id="heart_rate" class="form-control" min="30" max="220" placeholder="72">
                                            <span class="input-group-addon">bpm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.oxygen_saturation') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control" min="70" max="100" placeholder="98">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary">{{ __('medical_cases.general_measurements') }}</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.temperature') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="temperature" id="temperature" class="form-control" min="35" max="42" step="0.1" placeholder="36.5">
                                            <span class="input-group-addon">&deg;C</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.respiratory_rate') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" min="8" max="40" placeholder="16">
                                            <span class="input-group-addon">/min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.weight') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="weight" id="weight" class="form-control" min="1" max="300" step="0.1" placeholder="70">
                                            <span class="input-group-addon">kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.height') }}</label>
                                    <div class="col-md-7">
                                        <div class="input-group">
                                            <input type="number" name="height" id="height" class="form-control" min="50" max="250" step="0.1" placeholder="170">
                                            <span class="input-group-addon">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.notes') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="vital_notes" id="vital_notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn green" id="btn_save_vital_sign" onclick="saveVitalSign()">{{ __('common.save_record') }}</button>
            </div>
        </div>
    </div>
</div>
