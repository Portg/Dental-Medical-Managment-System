@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/unpaid-invoices.css') }}">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-wallet"></i>
                    <span class="caption-subject">{{ __('report.unpaid_invoices_report') }}</span>
                </div>
            </div>
            <div class="portlet-body">

                {{-- 筛选 --}}
                <div class="filter-row">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="control-label">{{ __('report.start_date') }}</label>
                            <input type="text" class="form-control datepicker" id="start_date">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">{{ __('report.end_date') }}</label>
                            <input type="text" class="form-control datepicker" id="end_date">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">{{ __('report.payment_status') }}</label>
                            <select class="form-control" id="payment_status">
                                <option value="">{{ __('report.all') }}</option>
                                <option value="unpaid">{{ __('report.status_unpaid') }}</option>
                                <option value="partial">{{ __('report.status_partial') }}</option>
                                <option value="overdue">{{ __('report.status_overdue') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">&nbsp;</label>
                            <div>
                                <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 统计卡片 --}}
                <div id="summary-cards" class="stat-cards" style="margin: 15px 0; display:none;">
                    <div class="stat-card highlight">
                        <div class="stat-value" id="card-count">0</div>
                        <div class="stat-label">{{ __('report.unpaid_invoice_count') }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="card-outstanding">¥0</div>
                        <div class="stat-label">{{ __('report.total_outstanding') }}</div>
                    </div>
                </div>

                {{-- 数据表 --}}
                <table id="unpaidTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('report.invoice_no') }}</th>
                            <th>{{ __('report.patient_name') }}</th>
                            <th>{{ __('report.phone') }}</th>
                            <th>{{ __('report.invoice_date') }}</th>
                            <th>{{ __('report.due_date') }}</th>
                            <th class="text-right">{{ __('report.total_amount') }}</th>
                            <th class="text-right">{{ __('report.amount_paid') }}</th>
                            <th class="text-right">{{ __('report.outstanding_balance') }}</th>
                            <th>{{ __('report.payment_status') }}</th>
                            <th>{{ __('report.doctor_name') }}</th>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('report')), 'report');
    window.UnpaidInvoicesConfig = {
        locale:  '{{ app()->getLocale() }}',
        dataUrl: '{{ url("unpaid-invoices") }}',
        statusLabels: {
            'unpaid':  '{{ __('report.status_unpaid') }}',
            'partial': '{{ __('report.status_partial') }}',
            'overdue': '{{ __('report.status_overdue') }}'
        }
    };
</script>
<script src="{{ asset('include_js/unpaid_invoices_report.js') }}?v={{ filemtime(public_path('include_js/unpaid_invoices_report.js')) }}"></script>
@endsection
