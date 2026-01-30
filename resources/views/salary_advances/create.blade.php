<div class="modal fade" id="scale-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('salary_advances.employee_salary_payment_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="scale-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.employee') }} </label>
                                <select id="employee" name="employee" class="form-control"
                                        style="width: 100%;"></select>
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.payment_month') }}</label>
                                <input type="text" name="advance_month" placeholder="yyyy-mm" id="monthsOnly"
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.payment_classification') }}</label><br>
                                <input type="radio" name="payment_classification" value="Salary"> {{ __('salary_advances.salary') }}<br>
                                <input type="radio" name="payment_classification" value="Advance"> {{ __('salary_advances.advance') }}<br>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.amount') }}</label>
                                <input type="number" name="amount" placeholder="{{ __('salary_advances.enter_amount') }}" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.payment_date_label') }}</label>
                                <input type="text" name="payment_date" placeholder="yyyy-mm-dd" id="datepicker"
                                       class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="text-primary">{{ __('salary_advances.payment_method') }}</label><br>
                                <input type="radio" name="payment_method" value="Cash"> {{ __('salary_advances.cash') }}<br>
                                <input type="radio" name="payment_method" value="Bank Transfer"> {{ __('salary_advances.bank_transfer') }}<br>
                                <input type="radio" name="payment_method" value="Cheque"> {{ __('salary_advances.cheque') }}<br>
                                <input type="radio" name="payment_method" value="Mobile Money"> {{ __('salary_advances.mobile_money') }}<br>
                                <input type="radio" name="payment_method" value="Online Wallet"> {{ __('salary_advances.online_wallet') }}<br>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('salary_advances.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('salary_advances.close') }}</button>
            </div>
        </div>
    </div>
</div>


