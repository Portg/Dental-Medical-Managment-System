<div class="modal fade" id="payment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('invoices.record_payment_for_invoice') }} </h4>
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
                    <input type="hidden" id="invoice_id" name="invoice_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.payment_date') }} </label>
                        <input type="text" name="payment_date" id="datepicker" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.amount') }} </label>
                        <input type="text" name="amount" placeholder="{{ __('invoices.enter_amount_here') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.payment_method') }} </label><br>
                        <input type="radio" name="payment_method" value="Cash"> {{ __('invoices.cash') }}<br>
                        <input type="radio" name="payment_method" value="Insurance"> {{ __('invoices.insurance') }}<br>
                        <input type="radio" name="payment_method" value="Online Wallet"> {{ __('invoices.online_wallet') }}<br>
                        <input type="radio" name="payment_method" value="Mobile Money"> {{ __('invoices.mobile_money') }}<br>
                        <input type="radio" name="payment_method" value="Cheque"> {{ __('invoices.cheque') }}<br>
                        <input type="radio" name="payment_method" value="Self Account"> {{ __('invoices.self_account') }}<br>
                    </div>
                    <div id="cheque_payment">
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.cheque_no') }} </label>
                            <input type="text" name="cheque_no" id="cheque_no" placeholder="{{ __('invoices.enter_cheque_no_here') }}"
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.account_name') }}</label>
                            <input type="text" name="account_name" placeholder="{{ __('invoices.enter_account_name_here') }}"
                                   class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.bank_name') }} </label>
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
                    <div class="self_account">
                        <div class="form-group">
                            <label class="text-primary">{{ __('invoices.self_account') }} </label>
                            <select id="self_account_id" name="self_account_id" class="form-control"
                                    style="width: 100%;"></select>
                        </div>
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


