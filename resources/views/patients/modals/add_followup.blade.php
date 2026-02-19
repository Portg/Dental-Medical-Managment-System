<div class="modal fade modal-form modal-form-lg" id="addFollowupModal" tabindex="-1" role="dialog" aria-labelledby="addFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addFollowupModalLabel">{{ __('patient_followups.add_followup') }}</h4>
            </div>
            <div class="modal-body">
                <form id="patientFollowupForm" class="form-horizontal">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.type') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <select name="followup_type" id="followup_type" class="form-control">
                                <option value="">{{ __('common.select') }}</option>
                                <option value="Phone">{{ __('patient_followups.type_phone') }}</option>
                                <option value="SMS">{{ __('patient_followups.type_sms') }}</option>
                                <option value="Email">{{ __('patient_followups.type_email') }}</option>
                                <option value="Visit">{{ __('patient_followups.type_visit') }}</option>
                                <option value="Other">{{ __('patient_followups.type_other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.scheduled_date') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="date" name="scheduled_date" id="scheduled_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.purpose') }} <span class="text-danger">*</span></label>
                        <div class="col-md-9">
                            <input type="text" name="purpose" id="purpose" class="form-control" placeholder="{{ __('patient_followups.purpose_placeholder') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.notes') }}</label>
                        <div class="col-md-9">
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="{{ __('patient_followups.notes_placeholder') }}"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.next_followup_date') }}</label>
                        <div class="col-md-9">
                            <input type="date" name="next_followup_date" id="next_followup_date" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn green" onclick="savePatientFollowup()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
