@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="note note-success">
    <p class="text-black-50"><a href="{{ url('expenses')}}" class="text-primary">{{ __('expense_items.view_expenses') }}</a>
        /@if(isset($purchase_details)) {{ $purchase_details->supplier_name }} / {{ __('expense_items.expense_no') }}
        : {{ $purchase_details->purchase_no }} {{ __('expense_items.payment_date') }}:
        ({{ \Carbon\Carbon::parse($purchase_details->purchase_date)->format('d/m/Y')}}) @endif
    </p>
</div>
<input type="hidden" value="{{ $expense_id }}" id="global_expense_id">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('expense_items.title') }}</span>
                    &nbsp; &nbsp; &nbsp
                    <button type="button" class="btn blue btn-outline sbold" onclick="Add_new_item()">{{ __('common.add_new') }}</button>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover" id="expense_items_table">
                    <thead>
                    <tr>
                        <th>{{ __('expense_items.hash') }}</th>
                        <th>{{ __('expense_items.item') }}</th>
                        <th>{{ __('expense_items.description') }}</th>
                        <th>{{ __('expense_items.quantity') }}</th>
                        <th>{{ __('expense_items.unit_price') }}</th>
                        <th>{{ __('expense_items.total_amount') }}</th>
                        <th>{{ __('expense_items.added_by') }}</th>
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
                    <span class="caption-subject font-dark bold uppercase">{{ __('expense_items.expense_payments') }}</span>
                </div>
                <div class="actions">
                    <div class="btn-group btn-group-devided">


                    </div>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover"
                       id="expense_payments_table">
                    <thead>
                    <tr>

                        <th>{{ __('expense_items.hash') }}</th>
                        <th>{{ __('expense_items.payment_date') }}</th>
                        <th>{{ __('expense_items.payment_account') }}</th>
                        <th>{{ __('expense_items.amount') }}</th>
                        <th>{{ __('expense_items.payment_method') }}</th>
                        <th>{{ __('expense_items.added_by') }}</th>
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
</div>


@include('expense_items.edit_item')
@include('expense_items.edit_payment')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>

    <script src="{{ asset('include_js/expense_items.js') }}"></script>
    <script src="{{ asset('include_js/expense_payments.js') }}"></script>
@endsection





