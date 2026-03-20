@extends('layouts.list-page')

@section('page_title', __('expenses.title'))
@section('table_id', 'expenses-table')

@section('header_actions')
    <a href="{{ url('export-expenses') }}" class="btn btn-default">
        <i class="icon-cloud-download"></i> {{ __('common.download_excel_report') }}
    </a>
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.period') }}</div>
            <select class="form-control" id="period_selector">
                <option value="">{{ __('datetime.time_periods.all') }}</option>
                <option value="Today">{{ __('datetime.time_periods.today') }}</option>
                <option value="Yesterday">{{ __('datetime.time_periods.yesterday') }}</option>
                <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
                <option value="Last week">{{ __('datetime.time_periods.last_week') }}</option>
                <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
                <option value="Last Month">{{ __('datetime.time_periods.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.date_range.start_date') }}</div>
            <input type="text" class="form-control start_date" id="filter_start_date">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.date_range.end_date') }}</div>
            <input type="text" class="form-control end_date" id="filter_end_date">
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('expenses.purchase_date') }}</th>
    <th>{{ __('expenses.supplier_name') }}</th>
    <th>{{ __('expenses.total_amount') }}</th>
    <th>{{ __('expenses.paid_amount') }}</th>
    <th>{{ __('expenses.outstanding') }}</th>
    <th>{{ __('expenses.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('expenses.create')
    @include('expenses.payment.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        LanguageManager.loadAllFromPHP({
            'expenses': @json(__('expenses')),
            'messages': @json(__('messages'))
        });

        window.ExpensesConfig = {
            expensesUrl: "{{ url('/expenses/') }}",
            chartOfAccts: @json($chart_of_accts->map(fn($c) => ['id' => $c->id, 'name' => $c->name]))
        };
    </script>
    <script src="{{ asset('include_js/expenses_index.js') }}?v={{ filemtime(public_path('include_js/expenses_index.js')) }}"></script>
@endsection
