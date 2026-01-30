<div class="modal fade" id="medical_case_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title" id="medical_case_modal_title">{{ __('medical_cases.add_case') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="medical_case_form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" name="case_id" id="case_id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.title') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="text" name="title" id="title" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.case_date') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="date" name="case_date" id="case_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.patient') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <select name="patient_id" id="patient_id" class="form-control select2" required>
                                            <option value="">{{ __('medical_cases.select_patient') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.doctor') }}</label>
                                    <div class="col-md-8">
                                        <select name="doctor_id" id="doctor_id" class="form-control select2">
                                            <option value="">{{ __('medical_cases.select_doctor') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.status') }}</label>
                                    <div class="col-md-8">
                                        <select name="status" id="status" class="form-control">
                                            <option value="Open">{{ __('medical_cases.status_open') }}</option>
                                            <option value="Follow-up">{{ __('medical_cases.status_follow_up') }}</option>
                                            <option value="Closed">{{ __('medical_cases.status_closed') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.chief_complaint') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.history_of_present_illness') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="history_of_present_illness" id="history_of_present_illness" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="closing_notes_row" style="display: none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('medical_cases.closing_notes') }}</label>
                                    <div class="col-md-10">
                                        <textarea name="closing_notes" id="closing_notes" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn_save_case" onclick="saveMedicalCase()">{{ __('common.save_record') }}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
