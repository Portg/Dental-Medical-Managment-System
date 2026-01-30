<div class="modal fade" id="payment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('invoices.record_payment_invoice') }} </h4>
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
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="receipt_id" name="receipt_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.payment_date') }}</label>
                        <input type="text" name="payment_date" id="datepicker" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.amount') }} </label>
                        <input type="text" name="amount" placeholder="enter amount here" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.enter_amount_here') }}</label><br>
                        <input type="radio" name="payment_method" value="Cash">{{ __('invoices_show.cash') }}<br>
                        <input type="radio" name="payment_method" value="Insurance">{{ __('invoices_show.insurance') }}<br>
                        <input type="radio" name="payment_method" value="Online Wallet">{{ __('invoices_show.online_wallet') }}<br>
                        <input type="radio" name="payment_method" value="Mobile Money">{{ __('invoices_show.mobile_money') }}<br>
                        <input type="radio" name="payment_method" value="Cheque">{{ __('invoices_show.cheque') }}<br>
                        <input type="radio" name="payment_method" value="Self Account">{{ __('invoices_show.self_account') }}<br>
                    </div>
                    <div id="cheque_payment">
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.cheque_no') }}</label>
                            <input type="text" name="cheque_no" id="cheque_no" placeholder="{{ __('invoices.enter_cheque_no_here') }}"
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.account_name') }}</label>
                            <input type="text" name="account_name" placeholder="{{ __('invoices.enter_account_name_here') }}"
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.bank_name') }}</label>
                            <input type="text" name="bank_name" placeholder="{{ __('invoices.enter_bank_name_here') }}"
                                   class="form-control">
                        </div>
                    </div>
                    <div class="insurance_company">
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.insurance_company') }} </label>
                            <select id="company" name="insurance_company_id" class="form-control"
                                    style="width: 100%;"></select>
                        </div>
                    </div>


                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btnSave" onclick="update_payment_record()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


