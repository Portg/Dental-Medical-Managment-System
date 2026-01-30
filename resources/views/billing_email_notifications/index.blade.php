@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('billing_notifications.email_notifications') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group pull-right">
                                {{--                                <a href="{{ url('export-sms-report') }}" class="text-danger">--}}
                                {{--                                    <i class="icon-cloud-download"></i>{{ __('common.download_excel_report') }} </a>--}}
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="col-md-12">

                    <form action="#" class="form-horizontal">
                        <div class="form-body">

                            <div class="row">
                                <div class="col-md-6">

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.period')}</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="period_selector">
                                                <option>{{__('datetime.time_periods.all')}}</option>
                                                <option value="Today">{{__('datetime.time_periods.today')}}</option>
                                                <option value="Yesterday">{{__('datetime.time_periods.yesterday')}}</option>
                                                <option value="This week">{{__('datetime.time_periods.this_week')}}</option>
                                                <option value="Last week">{{__('datetime.time_periods.last_week')}}</option>
                                                <option value="This Month">{{__('datetime.time_periods.this_month')}}</option>
                                                <option value="Last Month">{{__('datetime.time_periods.last_month')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.date_range.start_date')}}</label>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control start_date"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.date_range.end_date')}}</label>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button type="button" id="customFilterBtn" class="btn purple-intense">{{ __('billing_notifications.filter_notifications') }}
                                            </button>
                                            <button type="button" class="btn default">{{__('common.clear')}}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <br>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="email-notifications-table">
                    <thead>
                    <tr>
                        <th>{{__('common.id') }}</th>
                        <th>{{__('billing_notifications.sent_date')}}</th>
                        <th>{{ __('billing_notifications.email') }}</th>
                        <th>{{ __('billing_notifications.message') }}</th>
                        <th>{{ __('billing_notifications.message_type') }}</th>
                        <th>{{ __('billing_notifications.message_status') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
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
        });


        $(function () {
            default_todays_data();  //filter patient date

            var table = $('#email-notifications-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),  // 使用当前语言配置
                ajax: {
                    url: "{{ url('/billing-notifications/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'Bfrtip',
                buttons: {
                    buttons: [
                        // {extend: 'pdfHtml5', className: 'pdfButton'},
                        // {extend: 'excelHtml5', className: 'excelButton'},

                    ]
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'email', name: 'email'},
                    {data: 'message', name: 'message'},
                    {data: 'notification_type', name: 'notification_type'},
                    {data: 'status', name: 'status'},
                ]
            });


        });
        $('#customFilterBtn').click(function () {
            $('#email-notifications-table').DataTable().draw(true);
        });

        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);
            if (status) {
                let oTable = $('#email-notifications-table').dataTable();
                oTable.fnDraw(false);
            }
        }


    </script>
@endsection





