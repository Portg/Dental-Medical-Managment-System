@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('sms.sms_manager_outbox'))
@section('table_id', 'sms-table')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('export-sms-report') }}" class="text-danger">
        <i class="icon-cloud-download"></i> {{ __('sms.download_excel_report') }}
    </a>
@endsection

{{-- ========================================================================
     Filter Area
     ======================================================================== --}}
@section('filter_area')
    <div class="filter-row">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">{{ __('sms.period') }}</label>
                    <select class="form-control" id="period_selector">
                        <option>{{ __('sms.all') }}</option>
                        <option value="Today">{{ __('sms.today') }}</option>
                        <option value="Yesterday">{{ __('sms.yesterday') }}</option>
                        <option value="This week">{{ __('sms.this_week') }}</option>
                        <option value="Last week">{{ __('sms.last_week') }}</option>
                        <option value="This Month">{{ __('sms.this_month') }}</option>
                        <option value="Last Month">{{ __('sms.last_month') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">{{ __('sms.start_date') }}</label>
                    <input type="text" class="form-control start_date">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">{{ __('sms.end_date') }}</label>
                    <input type="text" class="form-control end_date">
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
                <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('sms.id') }}</th>
    <th>{{ __('sms.sent_date') }}</th>
    <th>{{ __('sms.phone_no') }}</th>
    <th>{{ __('sms.message') }}</th>
    <th>{{ __('sms.message_type') }}</th>
    <th>{{ __('sms.message_price') }}</th>
    <th>{{ __('sms.message_status') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
    <script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        function default_todays_data() {
            // initially load today's date filtered data
            $('.start_date').val(formatDate(thisMonth()));
            $('.end_date').val(todaysDate());
            $("#period_selector").val('This Month');
        }

        $('#period_selector').on('change', function () {
            switch (this.value) {
                case'Today':
                    $('.start_date').val(todaysDate());
                    $('.end_date').val(todaysDate());
                    break;
                case'Yesterday':
                    $('.start_date').val(YesterdaysDate());
                    $('.end_date').val(YesterdaysDate());
                    break;
                case'This week':
                    $('.start_date').val(thisWeek());
                    $('.end_date').val(todaysDate());
                    break;
                case'Last week':
                    lastWeek();
                    break;
                case'This Month':
                    $('.start_date').val(formatDate(thisMonth()));
                    $('.end_date').val(todaysDate());
                    break;
                case'Last Month':
                    lastMonth();
                    break;
            }
            doSearch();
        });


        $(function () {
            default_todays_data();  //filter patient date

            dataTable = $('#sms-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/outbox-sms/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'phone_number', name: 'phone_number'},
                    {data: 'message', name: 'message'},
                    {data: 'type', name: 'type'},
                    {data: 'cost', name: 'cost'},
                    {data: 'status', name: 'status'},
                ]
            });

            setupEmptyStateHandler();
        });

    </script>
@endsection
