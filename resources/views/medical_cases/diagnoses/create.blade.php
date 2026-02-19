<div class="modal fade modal-form modal-form-lg" id="diagnosis_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="diagnosis_modal_title">{{ __('medical_cases.add_diagnosis') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="diagnosis_form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" name="diagnosis_id" id="diagnosis_id">
                    <input type="hidden" name="medical_case_id" id="diagnosis_medical_case_id">
                    <input type="hidden" name="patient_id" id="diagnosis_patient_id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.diagnosis_name') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="text" name="diagnosis_name" id="diagnosis_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.icd_code') }}</label>
                                    <div class="col-md-8">
                                        <input type="text" name="icd_code" id="icd_code" class="form-control" placeholder="e.g., K02.1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.diagnosis_date') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-7">
                                        <input type="date" name="diagnosis_date" id="diagnosis_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.status') }}</label>
                                    <div class="col-md-8">
                                        <select name="diagnosis_status" id="diagnosis_status" class="form-control">
                                            <option value="Active">{{ __('medical_cases.diagnosis_status_active') }}</option>
                                            <option value="Resolved">{{ __('medical_cases.diagnosis_status_resolved') }}</option>
                                            <option value="Chronic">{{ __('medical_cases.diagnosis_status_chronic') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.severity') }}</label>
                                    <div class="col-md-8">
                                        <select name="severity" id="severity" class="form-control">
                                            <option value="">{{ __('medical_cases.select_severity') }}</option>
                                            <option value="Mild">{{ __('medical_cases.severity_mild') }}</option>
                                            <option value="Moderate">{{ __('medical_cases.severity_moderate') }}</option>
                                            <option value="Severe">{{ __('medical_cases.severity_severe') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="resolved_date_row" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.resolved_date') }}</label>
                                    <div class="col-md-8">
                                        <input type="date" name="resolved_date" id="resolved_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.notes') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="diagnosis_notes" id="diagnosis_notes" class="form-control template-enabled phrase-enabled" data-template-type="diagnosis" rows="3" placeholder="{{ __('templates.type_slash_to_search') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn_save_diagnosis" onclick="saveDiagnosis()">{{ __('common.save_record') }}</button>
            </div>
        </div>
    </div>
</div>
