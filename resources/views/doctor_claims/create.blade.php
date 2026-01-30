<div class="modal fade" id="claims-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">{{ __('doctor_claims.doctor_claim_approval_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="claims-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="">{{ __('doctor_claims.claim_amount') }}</label>
                        <input type="number" id="claim_amount" class="form-control" readonly placeholder="{{ __('doctor_claims.enter_claim_amount') }}"
                               name="claim_amount"/>
                    </div>
                    <div class="form-group">
                        <label class="">{{ __('doctor_claims.insurance_amount') }}</label>
                        <input type="number" id="insurance_amount" class="form-control" placeholder="{{ __('doctor_claims.enter_insurance_amount') }}"
                               name="insurance_amount"/>
                    </div>

                    <div class="form-group">
                        <label class="">{{ __('doctor_claims.cash_amount') }}</label>
                        <input type="number" id="cash_amount" class="form-control" placeholder="{{ __('doctor_claims.enter_cash_amount') }}"
                               name="cash_amount"/>
                    </div>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


