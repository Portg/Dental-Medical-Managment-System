<div class="modal fade modal-form" id="claims-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('doctor_claims.doctor_claim_approval_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="claims-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">

                    @include('components.form.text-field', [
                        'name' => 'claim_amount',
                        'label' => __('doctor_claims.claim_amount'),
                        'type' => 'number',
                        'placeholder' => __('doctor_claims.enter_claim_amount'),
                        'readonly' => true,
                    ])

                    @include('components.form.text-field', [
                        'name' => 'insurance_amount',
                        'label' => __('doctor_claims.insurance_amount'),
                        'type' => 'number',
                        'placeholder' => __('doctor_claims.enter_insurance_amount'),
                    ])

                    @include('components.form.text-field', [
                        'name' => 'cash_amount',
                        'label' => __('doctor_claims.cash_amount'),
                        'type' => 'number',
                        'placeholder' => __('doctor_claims.enter_cash_amount'),
                    ])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
