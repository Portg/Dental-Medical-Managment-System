<div class="modal fade" id="payment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('doctor_claim.payments.doctor_claims_payment') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="payment-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="claim_id" name="claim_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('doctor_claim.payments.payment_date') }}</label>
                        <input type="text" name="payment_date" id="datepicker" placeholder="{{ __('doctor_claim.payments.enter_payment_date') }}"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('doctor_claim.payments.amount') }} </label>
                        <input type="text" name="amount" id="amount" placeholder="{{ __('doctor_claim.payments.enter_amount_here') }}"
                               class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_payment_record()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


