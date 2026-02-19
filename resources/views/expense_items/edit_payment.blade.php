<div class="modal fade modal-form" id="payment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('expense_items.payments.title') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="payment-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="payment_id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.payment_date') }} </label>
                        <input type="text" placeholder="{{ __('datetime.yyyy_mm_dd') }}" name="payment_date" id="datepicker2"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.payment_method') }}</label><br>
                        <input type="radio" name="payment_method" value="Cash">  {{ __('expense_items.payments.cash') }}<br>
                        <input type="radio" name="payment_method" value="Mobile Money"> {{ __('expense_items.payments.mobile_money') }}<br>
                        <input type="radio" name="payment_method" value="Cheque"> {{ __('expense_items.payments.cheque') }}<br>
                        <input type="radio" name="payment_method" value="Bank Wire Transfer"> {{ __('expense_items.payments.bank_wire_transfer') }}<br>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.amount') }} </label>
                        <input type="number" name="amount" placeholder="{{ __('expense_items.enter_amount_here') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('expense_items.payment_account') }} </label><br>
                        <select class="form-control payment_account" name="payment_account">
                            <option value="">{{ __('expense_items.payments.choose_payment_account') }}</option>
                            @foreach($payment_accts as $acct)
                                <option value="{{ $acct->id }}">{{ $acct->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btnSave" onclick="update_payment_record()">{{ __('common.save_record') }}
                </button>
            </div>
        </div>
    </div>
</div>


