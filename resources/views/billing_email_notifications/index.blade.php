@extends('layouts.list-page')

@section('page_title', __('billing_notifications.email_notifications'))

@section('table_id', 'email-notifications-table')

@section('header_actions')
    {{-- Read-only list, no create or export buttons --}}
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('billing_notifications.sent_date') }}</th>
    <th>{{ __('billing_notifications.email') }}</th>
    <th>{{ __('billing_notifications.message') }}</th>
    <th>{{ __('billing_notifications.message_type') }}</th>
    <th>{{ __('billing_notifications.message_status') }}</th>
@endsection

@section('filter_area')
<div class="row filter-row">
    <div class="col-md-4">
        <label>{{ __('datetime.period') }}</label>
        <select class="form-control" id="period_selector">
            <option>{{ __('datetime.time_periods.all') }}</option>
            <option value="Today">{{ __('datetime.time_periods.today') }}</option>
            <option value="Yesterday">{{ __('datetime.time_periods.yesterday') }}</option>
            <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
            <option value="Last week">{{ __('datetime.time_periods.last_week') }}</option>
            <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
            <option value="Last Month">{{ __('datetime.time_periods.last_month') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>{{ __('datetime.date_range.start_date') }}</label>
        <input type="text" class="form-control start_date">
    </div>
    <div class="col-md-3">
        <label>{{ __('datetime.date_range.end_date') }}</label>
        <input type="text" class="form-control end_date">
    </div>
    <div class="col-md-2 text-right filter-actions" style="padding-top: 24px;">
        <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
        <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
    </div>
</div>
@endsection

@section('page_js')
<script type="text/javascript">
    /**
     * Set default date range to This Month
     */
    function default_todays_data() {
        $('.start_date').val(formatDate(thisMonth()));
        $('.end_date').val(todaysDate());
        $('#period_selector').val('This Month');
    }

    /**
     * Custom clear filters - restore default period and dates
     */
    function clearCustomFilters() {
        default_todays_data();
    }

    /**
     * Period selector change handler
     */
    $('#period_selector').on('change', function () {
        switch (this.value) {
            case 'Today':
                $('.start_date').val(todaysDate());
                $('.end_date').val(todaysDate());
                break;
            case 'Yesterday':
                $('.start_date').val(YesterdaysDate());
                $('.end_date').val(YesterdaysDate());
                break;
            case 'This week':
                $('.start_date').val(thisWeek());
                $('.end_date').val(todaysDate());
                break;
            case 'Last week':
                lastWeek();
                break;
            case 'This Month':
                $('.start_date').val(formatDate(thisMonth()));
                $('.end_date').val(todaysDate());
                break;
            case 'Last Month':
                lastMonth();
                break;
        }
        doSearch();
    });

    $(function () {
        // Set default date range
        default_todays_data();

        // Initialize DataTable
        dataTable = $('#email-notifications-table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/billing-notifications/') }}",
                data: function (d) {
                    d.start_date = $('.start_date').val();
                    d.end_date = $('.end_date').val();
                    d.search = $('input[type="search"]').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
                {data: 'created_at', name: 'created_at'},
                {data: 'email', name: 'email'},
                {data: 'message', name: 'message'},
                {data: 'notification_type', name: 'notification_type'},
                {data: 'status', name: 'status'},
            ]
        });

        // Setup empty state toggle on draw
        setupEmptyStateHandler();
    });
</script>
@endsection
