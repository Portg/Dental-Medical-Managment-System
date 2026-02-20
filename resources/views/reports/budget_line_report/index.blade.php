@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.budget_line_report'))
@section('table_id', 'payment-report')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('export-budget-line') }}" class="text-danger">
        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
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
                    <label class="control-label">{{ __('report.period') }}</label>
                    <select class="form-control" id="period_selector">
                        <option value="Today">{{ __('report.today') }}</option>
                        <option value="Yesterday">{{ __('report.yesterday') }}</option>
                        <option value="This week">{{ __('report.this_week') }}</option>
                        <option value="Last week">{{ __('report.last_week') }}</option>
                        <option value="This Month">{{ __('report.this_month') }}</option>
                        <option value="Last Month">{{ __('report.last_month') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('common.start_date') }}</label>
                    <input type="text" class="form-control start_date">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="control-label">{{ __('common.end_date') }}</label>
                    <input type="text" class="form-control end_date">
                </div>
            </div>
            <div class="col-md-2 text-right filter-actions">
                <label class="control-label">&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                    <button type="button" id="customFilterBtn" class="btn btn-primary">{{ __('common.search') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('report.budget_lines') }}</th>
    <th>{{ __('report.total_items') }}</th>
    <th>{{ __('common.total') }} {{ __('common.amount') }}</th>
@endsection

{{-- ========================================================================
     Modals
     ======================================================================== --}}
@section('modals')
    @include('reports.budget_line_report.preview_budget_line')
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

        function clearCustomFilters() {
            default_todays_data();
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
        });


        $(function () {
            default_todays_data();  //filter  date

            dataTable = $('#payment-report').DataTable({
                language: LanguageManager.getDataTableLang(),
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('/budget-line-report/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.doctor_id = $('.doctor_id').val();

                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'budget_line', name: 'budget_line'},
                    {data: 'total_qty', name: 'total_qty'},
                    {data: 'product_price', name: 'product_price'},
                ]
            });

            setupEmptyStateHandler();
        });

        $('#customFilterBtn').click(function () {
            dataTable.draw(true);
        });
    </script>
@endsection
