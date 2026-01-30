<div class="modal fade" id="allowances-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('payslips.allowances_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="allowances-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="allowance_id" name="id">
                    <input type="hidden" id="allowance_pay_slip_id" name="pay_slip_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.allowance') }} </label><br>
                        <input type="radio" name="allowance" value="House Rent Allowance"> {{ __('payslips.house_rent_allowance') }}<br>
                        <input type="radio" name="allowance" value="Medical Allowance"> {{ __('payslips.medical_allowance') }}<br>
                        <input type="radio" name="allowance" value="Bonus"> {{ __('payslips.bonus') }}<br>
                        <input type="radio" name="allowance" value="Dearness Allowance"> {{ __('payslips.dearness_allowance') }}<br>
                        <input type="radio" name="allowance" value="Travelling Allowance"> {{ __('payslips.travelling_allowance') }}<br>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.amount') }} </label>
                        <input type="number" name="amount" placeholder="{{ __('payslips.enter_amount_here') }}" class="form-control">
                    </div>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-allowance" onclick="record_allowances()">{{ __('payslips.save_record') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('payslips.close') }}</button>
            </div>
        </div>
    </div>
</div>


