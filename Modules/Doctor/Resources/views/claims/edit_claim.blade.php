<div class="modal fade modal-form" id="claims-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('doctor_claims.claims_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="claims-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="appointment_id" name="appointment_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('doctor_claims.treatment_amount') }}</label>
                        <input type="number" class="form-control" placeholder="{{ __('doctor_claims.enter_claim_amount') }}" name="amount"/>
                    </div>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="update_record()">{{ __('common.update_record') }}</button>
            </div>
        </div>
    </div>
</div>
