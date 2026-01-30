<div class="modal fade" id="viewFollowupModal" tabindex="-1" role="dialog" aria-labelledby="viewFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="viewFollowupModalLabel">{{ __('patient_followups.view_followup') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.followup_no') }}:</strong><br>
                            <span id="view_followup_no"></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.status') }}:</strong><br>
                            <span id="view_status"></span>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.type') }}:</strong><br>
                            <span id="view_followup_type"></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.scheduled_date') }}:</strong><br>
                            <span id="view_scheduled_date"></span>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>{{ __('patient_followups.purpose') }}:</strong><br>
                            <span id="view_purpose"></span>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>{{ __('patient_followups.notes') }}:</strong><br>
                            <span id="view_notes"></span>
                        </p>
                    </div>
                </div>
                <div class="row" id="view_outcome_row" style="display:none;">
                    <div class="col-md-12">
                        <p><strong>{{ __('patient_followups.outcome') }}:</strong><br>
                            <span id="view_outcome"></span>
                        </p>
                    </div>
                </div>
                <div class="row" id="view_completed_date_row" style="display:none;">
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.completed_date') }}:</strong><br>
                            <span id="view_completed_date"></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('patient_followups.next_followup_date') }}:</strong><br>
                            <span id="view_next_followup_date"></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
