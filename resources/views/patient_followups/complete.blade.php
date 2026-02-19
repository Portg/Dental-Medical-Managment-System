<div class="modal fade modal-form" id="completeFollowupModal" tabindex="-1" role="dialog" aria-labelledby="completeFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="completeFollowupModalLabel">{{ __('patient_followups.complete_followup') }}</h4>
            </div>
            <div class="modal-body">
                <form id="completeFollowupForm" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="complete_followup_id" id="complete_followup_id">

                    <div class="form-group">
                        <label class="control-label col-md-3 text-primary">{{ __('patient_followups.outcome') }}</label>
                        <div class="col-md-9">
                            <textarea name="complete_outcome" id="complete_outcome" class="form-control" rows="4" placeholder="{{ __('patient_followups.outcome_placeholder') }}"></textarea>
                        </div>
                    </div>

                    <p class="text-muted text-center">
                        {{ __('patient_followups.complete_confirmation') }}
                    </p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn green" onclick="confirmCompleteFollowup()">{{ __('patient_followups.mark_complete') }}</button>
            </div>
        </div>
    </div>
</div>
