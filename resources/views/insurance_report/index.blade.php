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
                    <span class="caption-subject"> {{ __('insurance_reports.title') }} </span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">

                        </div>
                    </div>
                </div>
                <br>
                <div class="col-md-12">

                    <form class="form-inline" role="form">
                        <div class="form-group">
                            <div class="input-icon">
                                <select id="company" name="insurance_company_id" class="form-control"
                                        style="width: 100%;"></select>
                                <div class="help-block"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="exampleInputEmail22">{{ __('datetime.date_range.start_date') }}</label>
                            <div class="input-icon">
                                <i class="fa fa-calendar"></i>
                                <input type="date" name="start_date" id="start_date"
                                       class="form-control datepicker-autoclose" placeholder="{{ __('datetime.placeholder_start_date') }}">
                                <div class="help-block"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="exampleInputPassword42">{{__('datetime.date_range.end_date')}}</label>
                            <div class="input-icon">
                                <i class="fa fa-calendar"></i>
                                <input type="date" name="end_date" id="end_date"
                                       class="form-control datepicker-autoclose"
                                       placeholder="{{ __('datetime.placeholder_end_date') }}">
                                <div class="help-block"></div>
                            </div>
                        </div>
                        <button type="button" id="btnFiterSubmitSearch" class="btn green-meadow">{{ __('insurance_reports.filter_data') }}</button>
                    </form>
                </div>
                <br>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('insurance_reports.date') }}</th>
                        <th>{{ __('insurance_reports.insurance_company') }}</th>
                        <th>{{ __('insurance_reports.invoice_no') }}</th>
                        <th>{{ __('insurance_reports.customer') }}</th>
                        <th>{{ __('insurance_reports.procedure') }}</th>
                        <th>{{ __('insurance_reports.fees') }}</th>
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
@include('insurance_companies.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {
            // 批量加载
            LanguageManager.loadAllFromPHP({
                'insurance_report' : @json(__('insurance_reports'))
            });
            var table = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/insurance-reports/') }}",
                    data: function (d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.company = $('#company').val();
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
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'insurance_company', name: 'insurance_company'},
                    {data: 'invoice_no', name: 'invoice_no'},
                    {data: 'patient', name: 'patient'},
                    {data: 'services_provided', name: 'services_provided'},
                    {data: 'amount', name: 'amount'}
                ]
            });


        });
        $('#btnFiterSubmitSearch').click(function () {
            $('#sample_1').DataTable().draw(true);
        });
        //filter insurance companies
        $('#company').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('insurance_reports.choose_insurance_company') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-insurance-company',
                dataType: 'json',
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);

            setTimeout(function () {
                location.reload();
            }, 1900);
        }


    </script>
@endsection





