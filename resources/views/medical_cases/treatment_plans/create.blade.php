<div class="modal fade" id="treatment_plan_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title" id="treatment_plan_modal_title">{{ __('medical_cases.add_treatment_plan') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="treatment_plan_form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" name="treatment_plan_id" id="treatment_plan_id">
                    <input type="hidden" name="medical_case_id" id="plan_medical_case_id">
                    <input type="hidden" name="patient_id" id="plan_patient_id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="control-label col-md-3 text-primary">{{ __('medical_cases.plan_name') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-9">
                                        <input type="text" name="plan_name" id="plan_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.priority') }}</label>
                                    <div class="col-md-8">
                                        <select name="priority" id="priority" class="form-control">
                                            <option value="Low">{{ __('medical_cases.priority_low') }}</option>
                                            <option value="Medium" selected>{{ __('medical_cases.priority_medium') }}</option>
                                            <option value="High">{{ __('medical_cases.priority_high') }}</option>
                                            <option value="Urgent">{{ __('medical_cases.priority_urgent') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.description') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="description" id="plan_description" class="form-control template-enabled phrase-enabled" data-template-type="treatment_plan" rows="2" placeholder="{{ __('templates.type_slash_to_search') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.planned_procedures') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="planned_procedures" id="planned_procedures" class="form-control template-enabled phrase-enabled" data-template-type="treatment_plan" rows="3" placeholder="{{ __('medical_cases.planned_procedures_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.estimated_cost') }}</label>
                                    <div class="col-md-7">
                                        <input type="number" name="estimated_cost" id="estimated_cost" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.start_date') }}</label>
                                    <div class="col-md-7">
                                        <input type="date" name="start_date" id="start_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.target_completion_date') }}</label>
                                    <div class="col-md-7">
                                        <input type="date" name="target_completion_date" id="target_completion_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.status') }}</label>
                                    <div class="col-md-8">
                                        <select name="plan_status" id="plan_status" class="form-control">
                                            <option value="Planned">{{ __('medical_cases.plan_status_planned') }}</option>
                                            <option value="In Progress">{{ __('medical_cases.plan_status_in_progress') }}</option>
                                            <option value="Completed">{{ __('medical_cases.plan_status_completed') }}</option>
                                            <option value="Cancelled">{{ __('medical_cases.plan_status_cancelled') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" id="actual_cost_row" style="display: none;">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.actual_cost') }}</label>
                                    <div class="col-md-7">
                                        <input type="number" name="actual_cost" id="actual_cost" class="form-control" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" id="actual_completion_date_row" style="display: none;">
                                <div class="form-group">
                                    <label class="control-label col-md-5 text-primary">{{ __('medical_cases.actual_completion_date') }}</label>
                                    <div class="col-md-7">
                                        <input type="date" name="actual_completion_date" id="actual_completion_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="completion_notes_row" style="display: none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.completion_notes') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="completion_notes" id="completion_notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn green" id="btn_save_treatment_plan" onclick="saveTreatmentPlan()">{{ __('common.save_record') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
