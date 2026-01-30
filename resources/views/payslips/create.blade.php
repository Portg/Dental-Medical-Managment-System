<div class="modal fade" id="scale-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('payslips.employee_payslip_form') }} </h4>
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
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.employee') }} </label>
                        <select id="employee" name="employee" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.payment_month') }}</label>
                        <input type="text" name="payslip_month" placeholder="yyyy-mm" id="monthsOnly"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.include_allowances') }}</label><br>
                        <input type="radio" name="allowances_include" value="Yes"> {{ __('payslips.yes') }}
                        <input type="radio" name="allowances_include" checked value="No"> {{ __('payslips.no') }}
                    </div>
                    <div class="form-group">
                        <table class="table table-bordered" id="AllowancesTable">
                            <tr>
                                <th class="text-primary">{{ __('payslips.allowance') }}</th>
                                <th class="text-primary">{{ __('payslips.amount') }}</th>
                                <th class="text-primary">{{ __('payslips.action') }}</th>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" name="addAllowance[0][allowance]">
                                        <option value="House Rent Allowance">{{ __('payslips.house_rent_allowance') }}</option>
                                        <option value="Medical Allowance">{{ __('payslips.medical_allowance') }}</option>
                                        <option value="Bonus">{{ __('payslips.bonus') }}</option>
                                        <option value="Dearness Allowance">{{ __('payslips.dearness_allowance') }}</option>
                                        <option value="Travelling Allowance">{{ __('payslips.travelling_allowance') }}</option>
                                        <option value="Overtime Allowance">{{ __('payslips.overtime_allowance') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                           name="addAllowance[0][allowance_amount]"
                                           placeholder="{{ __('payslips.enter_amount') }}"/>
                                </td>
                                <td>
                                    <button type="button" name="add" id="add_allowance" class="btn btn-info">{{ __('payslips.add_more') }}
                                    </button>
                                </td>
                            </tr>

                        </table>

                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('payslips.include_deductions') }}</label><br>
                        <input type="radio" name="deductions_include" value="Yes"> {{ __('payslips.yes') }}
                        <input type="radio" name="deductions_include" checked value="No"> {{ __('payslips.no') }}
                    </div>
                    <div class="form-group">
                        <table class="table table-bordered" id="DeductionsTable">
                            <tr>
                                <th class="text-primary">{{ __('payslips.deduction') }}</th>
                                <th class="text-primary">{{ __('payslips.amount') }}</th>
                                <th class="text-primary">{{ __('payslips.action') }}</th>
                            </tr>
                            <tr>
                                <td>
                                    <select class="form-control" name="addDeduction[0][deduction]">
                                        <option value="Loan">{{ __('payslips.nssf') }}</option>
                                        <option value="Tax">{{ __('payslips.payee') }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                           name="addDeduction[0][deduction_amount]"
                                           placeholder="{{ __('payslips.enter_amount') }}"/>
                                </td>
                                <td>
                                    <button type="button" name="add" id="add_deduction" class="btn btn-success">{{ __('payslips.add_more') }}
                                    </button>
                                </td>
                            </tr>

                        </table>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn purple" id="btn-save" onclick="save_data()">{{ __('payslips.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('payslips.close') }}</button>
            </div>
        </div>
    </div>
</div>


