<div class="modal fade" id="followupModal" tabindex="-1" role="dialog" aria-labelledby="followupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="followupModalLabel">{{ __('patient_followups.add_followup') }}</h4>
            </div>
            <div class="modal-body">
                <form id="followupForm" class="form-horizontal">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="followup_id" id="followup_id">

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.patient') }} <span class="text-danger">*</span></label>
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

                    <div class="form-group" id="status_group" style="display:none;">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.status') }}</label>
                        <div class="col-md-9">
                            <select name="status" id="status" class="form-control">
                                <option value="Pending">{{ __('patient_followups.status_pending') }}</option>
                                <option value="Completed">{{ __('patient_followups.status_completed') }}</option>
                                <option value="Cancelled">{{ __('patient_followups.status_cancelled') }}</option>
                                <option value="No Response">{{ __('patient_followups.status_no_response') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.notes') }}</label>
                        <div class="col-md-9">
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="{{ __('patient_followups.notes_placeholder') }}"></textarea>
                        </div>
                    </div>

                    <div class="form-group" id="outcome_group" style="display:none;">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.outcome') }}</label>
                        <div class="col-md-9">
                            <textarea name="outcome" id="outcome" class="form-control" rows="3" placeholder="{{ __('patient_followups.outcome_placeholder') }}"></textarea>
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
                <button type="button" class="btn btn-primary" onclick="saveFollowup()">{{ __('common.save') }}</button>
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
