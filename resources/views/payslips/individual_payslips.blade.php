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
                    <span class="caption-subject"> {{ __('payslips.payroll_management') }}/ {{ __('payslips.individual_payslips') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">

                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('payslips.id') }}</th>
                        <th>{{ __('payslips.payslip_month') }}</th>
                        <th>{{ __('payslips.basic_salary') }}</th>
                        <th>{{ __('payslips.total_advances') }}</th>
                        <th>{{ __('payslips.total_allowances') }}</th>
                        <th>{{ __('payslips.total_deductions') }}</th>
                        <th>{{ __('payslips.due_balance') }}</th>
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
    <span>{{ __('payslips.loading') }}</span>
</div>
@include('payslips.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {

            let table = $('#sample_1').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/individual-payslips/') }}",
                    data: function (d) {
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
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'payslip_month', name: 'payslip_month'},
                    {data: 'basic_salary', name: 'basic_salary'},
                    {data: 'total_advances', name: 'total_advances'},
                    {data: 'total_allowances', name: 'total_allowances'},
                    {data: 'total_deductions', name: 'total_deductions'},
                    {data: 'due_balance', name: 'due_balance'}
                ]
            });


        });

    </script>
@endsection





