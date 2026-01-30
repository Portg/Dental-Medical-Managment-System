<div class="modal fade" id="scale-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('employee_contracts.employee_contracts_form') }} </h4>
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
                                <label class="text-primary">{{ __('employee_contracts.employee') }}</label>
                                <select id="employee" name="employee" class="form-control"
                                        style="width: 100%;"></select>
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('employee_contracts.contract_type') }}</label><br>
                                <input type="radio" name="contract_type" value="Probation"> {{ __('employee_contracts.probation') }}<br>
                                <input type="radio" name="contract_type" value="Part Time"> {{ __('employee_contracts.part_time') }}<br>
                                <input type="radio" name="contract_type" value="Full Time"> {{ __('employee_contracts.full_time') }}<br>
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('employee_contracts.contract_start_date') }}</label>
                                <input type="text" name="start_date" placeholder="{{ __('datetime.format_date') }}" id="datepicker"
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="text-primary">{{ __('employee_contracts.contract_length') }}</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="number" name="contract_length" placeholder="{{ __('employee_contracts.eg_no_of_years') }}"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" name="contract_period" value="Months"> {{ __('employee_contracts.months') }}
                                        <input type="radio" name="contract_period" checked value="Years"> {{ __('employee_contracts.years') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('employee_contracts.payroll_type') }}</label><br>
                                <input type="radio" name="payroll_type" value="Salary"> {{ __('employee_contracts.salary') }}<br>
                                <input type="radio" name="payroll_type" value="Commission"> {{ __('employee_contracts.commission') }}<br>
                            </div>
                            <div class="form-group gross_section">
                                <label class="text-primary">{{ __('employee_contracts.gross_salary') }}</label>
                                <input type="number" name="gross_salary" placeholder="{{ __('employee_contracts.enter_amount') }}"
                                       class="form-control">
                            </div>
                            <div class="form-group commission_section">
                                <label class="text-primary">{{ __('employee_contracts.commission_percentage') }}</label>
                                <input type="number" name="commission_percentage" placeholder="{{ __('employee_contracts.enter_percentage') }}"
                                       class="form-control">
                            </div>
                        </div>
                    </div>


                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


