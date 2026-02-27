<div class="modal fade modal-form" id="refundModal" tabindex="-1" role="dialog" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="refundModalLabel">{{ __('members.refund_funds') }}</h4>
            </div>
            <div class="modal-body">
                <form id="refundForm" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="member_id" id="refund_member_id">
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.member_no') }}</label>
                        <div class="col-md-8">
                            <input type="text" id="refund_member_no" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.patient_name') }}</label>
                        <div class="col-md-8">
                            <input type="text" id="refund_patient_name" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.current_balance') }}</label>
                        <div class="col-md-8">
                            <input type="text" id="refund_current_balance" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.refund_amount') }} <span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <input type="number" name="amount" id="refund_amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.payment_method') }} <span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <select name="payment_method" id="refund_payment_method" class="form-control" required>
                                <option value="Cash">{{ __('members.payment_cash') }}</option>
                                <option value="Card">{{ __('members.payment_card') }}</option>
                                <option value="Bank Transfer">{{ __('members.payment_bank') }}</option>
                                <option value="Mobile Payment">{{ __('members.payment_mobile') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.refund_reason') }}</label>
                        <div class="col-md-8">
                            <textarea name="description" id="refund_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-danger" onclick="submitRefund()">{{ __('members.confirm_refund') }}</button>
            </div>
        </div>
    </div>
</div>
