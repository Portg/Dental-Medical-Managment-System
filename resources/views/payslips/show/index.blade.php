@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="note note-success">
    <p class="text-black-50"><a href="{{ url('payslips')}}" class="text-primary">{{ __('payslips.go_back_payslips') }} </a>
        /@if(isset($employee)) {{ $employee->full_name }} / {{ __('payslips.payslip_month') }}:
        {{ $employee->payslip_month }}  @endif
    </p>
</div>
<input type="hidden" value="{{ $pay_slip_id }}" id="global_pay_slip_id">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('payslips.allowances') }}</span>
                    &nbsp; &nbsp; &nbsp
                    <button type="button" class="btn blue btn-outline sbold" onclick="Add_new_allowance()">{{ __('payslips.add_more') }}</button>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover" id="allowances-table">
                    <thead>
                    <tr>
                        <th> #</th>
                        <th>{{ __('payslips.added_at') }}</th>
                        <th>{{ __('payslips.allowance') }}</th>
                        <th>{{ __('payslips.amount') }}</th>
                        <th>{{ __('payslips.added_by') }}</th>
                        <th>{{ __('payslips.edit') }}</th>
                        <th>{{ __('payslips.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="portlet light portlet-fit bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject font-dark bold uppercase">{{ __('payslips.deductions') }}</span>
                    &nbsp; &nbsp
                    <button type="button" class="btn blue btn-outline sbold" onclick="Add_new_deduction()">{{ __('payslips.add_more') }}</button>
                </div>
                <div class="actions">
                    <div class="btn-group btn-group-devided">


                    </div>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover"
                       id="deductions-table">
                    <thead>
                    <tr>

                        <th> #</th>
                        <th>{{ __('payslips.added_at') }}</th>
                        <th>{{ __('payslips.deduction') }}</th>
                        <th>{{ __('payslips.amount') }}</th>
                        <th>{{ __('payslips.added_by') }}</th>
                        <th>{{ __('payslips.edit') }}</th>
                        <th>{{ __('payslips.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@include('payslips.show.edit_allowances')
@include('payslips.show.edit_deductions')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>

    <script src="{{ asset('include_js/allowances.js') }}"></script>
    <script src="{{ asset('include_js/deductions.js') }}"></script>
@endsection





