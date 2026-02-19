<div class="modal fade modal-form" id="payment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('doctor_claim.payments.doctor_claims_payment') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="payment-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="claim_id" name="claim_id">

                    @include('components.form.text-field', [
                        'name' => 'payment_date',
                        'label' => __('doctor_claim.payments.payment_date'),
                        'placeholder' => __('doctor_claim.payments.enter_payment_date'),
                        'id' => 'datepicker',
                    ])

                    @include('components.form.text-field', [
                        'name' => 'amount',
                        'label' => __('doctor_claim.payments.amount'),
                        'placeholder' => __('doctor_claim.payments.enter_amount_here'),
                    ])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_payment_record()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
