<div class="modal fade" id="deposit-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('deposits.deposit_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="deposit-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="deposit_id" name="deposit_id">
                    <input type="hidden" id="self_account_id" name="self_account_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('deposits.payment_date') }} </label>
                        <input type="text" name="payment_date" placeholder="yyyy-mm-dd" id="datepicker"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('common.amount') }} </label>
                        <input type="text" name="amount" placeholder="{{ __('deposits.enter_amount') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('deposits.payment_method') }} </label><br>
                        <input type="radio" name="payment_method" value="Cash">{{ __('deposits.cash') }}<br>
                        <input type="radio" name="payment_method" value="Mobile Money">{{ __('deposits.mobile_money') }}<br>
                        <input type="radio" name="payment_method" value="Cheque">{{ __('deposits.cheque') }}<br>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-deposit" onclick="save_deposit()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


