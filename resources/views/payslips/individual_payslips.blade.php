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
    <script>
        window.IndividualPayslipsConfig = {
            ajaxUrl: "{{ url('/individual-payslips/') }}"
        };
    </script>
    <script src="{{ asset('include_js/individual_payslips.js') }}?v={{ filemtime(public_path('include_js/individual_payslips.js')) }}" type="text/javascript"></script>
@endsection





