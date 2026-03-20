@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/financial-detail.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-docs"></i>
                    <span class="caption-subject">{{ __('report.financial_detail_report') }}</span>
                </div>
            </div>
            <div class="portlet-body">

                {{-- Tab 导航 --}}
                <div class="report-tabs">
                    <ul class="nav nav-tabs" id="financialDetailTabs">
                        <li class="{{ $activeTab === 'payments' ? 'active' : '' }}">
                            <a href="#tab-payments" data-toggle="tab">{{ __('report.payment_detail') }}</a>
                        </li>
                        <li class="{{ $activeTab === 'refunds' ? 'active' : '' }}">
                            <a href="#tab-refunds" data-toggle="tab">{{ __('report.refund_detail') }}</a>
                        </li>
                        <li class="{{ $activeTab === 'expenses' ? 'active' : '' }}">
                            <a href="#tab-expenses" data-toggle="tab">{{ __('report.expense_detail') }}</a>
                        </li>
                        <li class="{{ $activeTab === 'employee' ? 'active' : '' }}">
                            <a href="#tab-employee" data-toggle="tab">{{ __('report.employee_billing_detail') }}</a>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">
                    {{-- Tab 1: 收款明细 --}}
                    <div id="tab-payments" class="tab-pane {{ $activeTab === 'payments' ? 'active' : '' }}">
                        <div class="filter-row">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.start_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="pay_start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.end_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="pay_end_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.payment_method') }}</label>
                                    <select class="form-control" id="pay_type">
                                        <option value="">{{ __('report.all') }}</option>
                                        @foreach($paymentTypes as $code => $name)
                                            <option value="{{ $code }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" id="pay_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="paymentsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('report.payment_date') }}</th>
                                    <th>{{ __('report.invoice_no') }}</th>
                                    <th>{{ __('report.patient_name') }}</th>
                                    <th>{{ __('report.payment_method') }}</th>
                                    <th class="text-right">{{ __('report.amount') }}</th>
                                    <th>{{ __('report.cashier') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- Tab 2: 退款明细 --}}
                    <div id="tab-refunds" class="tab-pane {{ $activeTab === 'refunds' ? 'active' : '' }}">
                        <div class="filter-row">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.start_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="ref_start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.end_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="ref_end_date">
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" id="ref_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="refundsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('report.refund_date') }}</th>
                                    <th>{{ __('report.invoice_no') }}</th>
                                    <th>{{ __('report.patient_name') }}</th>
                                    <th class="text-right">{{ __('report.amount') }}</th>
                                    <th>{{ __('common.reason') }}</th>
                                    <th>{{ __('report.operator') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    {{-- Tab 3: 支出明细 --}}
                    <div id="tab-expenses" class="tab-pane {{ $activeTab === 'expenses' ? 'active' : '' }}">
                        <div class="filter-row">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.start_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="exp_start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.end_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="exp_end_date">
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" id="exp_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="expensesTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('report.payment_date') }}</th>
                                    <th>{{ __('report.description') }}</th>
                                    <th>{{ __('report.supplier') }}</th>
                                    <th class="text-right">{{ __('report.amount') }}</th>
                                    <th>{{ __('report.operator') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    {{-- Tab 4: 员工收费明细 --}}
                    <div id="tab-employee" class="tab-pane {{ $activeTab === 'employee' ? 'active' : '' }}">
                        <div class="filter-row">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.start_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="emp_start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.end_date') }}</label>
                                    <input type="text" class="form-control datepicker" id="emp_end_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">{{ __('report.cashier') }}</label>
                                    <select class="form-control" id="emp_cashier">
                                        <option value="">{{ __('report.all') }}</option>
                                        @foreach($cashiers as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" id="emp_search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="employeeTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('report.payment_date') }}</th>
                                    <th>{{ __('report.invoice_no') }}</th>
                                    <th>{{ __('report.patient_name') }}</th>
                                    <th>{{ __('report.payment_method') }}</th>
                                    <th class="text-right">{{ __('report.amount') }}</th>
                                    <th>{{ __('report.cashier') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('report')), 'report');
    window.FinancialDetailConfig = {
        locale:  '{{ app()->getLocale() }}',
        dataUrl: '{{ url("financial-detail-report") }}'
    };
</script>
<script src="{{ asset('include_js/financial_detail_report.js') }}?v={{ filemtime(public_path('include_js/financial_detail_report.js')) }}"></script>
@endsection
