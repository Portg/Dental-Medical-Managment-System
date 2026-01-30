@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="note note-success">
    <p class="text-black-50"><a href="{{ url('self-accounts')}}" class="text-primary">{{ __('self_accounts.view_self_accounts') }} </a> /
        @if(isset($account_info))  {{ $account_info->account_holder." / ".$account_info->account_no  }} @endif
    </p>
</div>
<input type="hidden" value="@if(isset($account_info)) {{ $account_info->id }} @endif" id="global_self_account_id">
<div class="row">

    <div class="col-md-12">
        <div class="portlet light portlet-fit bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject font-dark bold uppercase">{{ __('self_accounts.self_account_deposits') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="#"
                                   onclick="AddDeposit()"> {{ __('common.add_new') }} <i
                                            class="fa fa-plus"></i> </a>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="self_account_deposits_table">
                    <thead>
                    <tr>

                        <th> #</th>
                        <th>{{ __('deposits.payment_date') }}</th>
                        <th>{{ __('common.amount') }}</th>
                        <th>{{ __('deposits.payment_method') }}</th>
                        <th>{{ __('self_accounts.added_by') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
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
                    <span class="caption-subject font-dark bold uppercase">{{ __('self_accounts.self_account_bill_payments') }}</span>
                </div>
                <div class="actions">

                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="self_account_bills_table">
                    <thead>
                    <tr>

                        <th> #</th>
                        <th>{{ __('self_accounts.invoice_no') }}</th>
                        <th>{{ __('self_accounts.patient') }}</th>
                        <th>{{ __('deposits.payment_date') }}</th>
                        <th>{{ __('common.amount') }}</th>
                        <th>{{ __('self_accounts.added_by') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@include('self_accounts.deposits.create')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>

    <script src="{{ asset('include_js/self_account_deposits.js') }}"></script>
    <script src="{{ asset('include_js/self_account_bills.js') }}"></script>
@endsection





