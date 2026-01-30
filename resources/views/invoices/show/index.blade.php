@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="note note-success">
    <p class="text-black-50">
        <a href="{{ url('invoices')}}" class="text-primary">{{ __('invoices.go_back_to_invoices') }}</a> /
        @if(isset($patient)) {{ $patient->surname." ".$patient->othername  }} @endif
    </p>
</div>
<input type="hidden" value="{{ $invoice_id }}" id="global_invoice_id">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('invoices.invoice') }}</span>
                    &nbsp; &nbsp; &nbsp

                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover" id="sample_2">
                    <thead>
                    <tr>
                        <th>{{ __('invoices.hash') }}</th>
                        <th>{{ __('invoices.tooth_numbers') }}</th>
                        <th>{{ __('invoices.quantity') }}</th>
                        <th>{{ __('invoices.unit_price') }}</th>
                        <th>{{ __('invoices.total_amount') }}</th>
                        <th>{{ __('invoices.procedure_doctor') }}</th>
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
                    <span class="caption-subject font-dark bold uppercase">{{ __('invoices.receipts') }}</span>
                </div>
                <div class="actions">
                    <div class="btn-group btn-group-devided">

                        <a href="{{ url('print-receipt/'.$invoice_id) }}" class="btn grey-salsa btn-sm"
                           target="_blank"> <i
                                    class="fa fa-print"></i>{{ __('print.print_receipt') }}</a>
                    </div>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_3">
                    <thead>
                    <tr>

                        <th>{{ __('invoices.hash') }}</th>
                        <th>{{ __('invoices.payment_date') }}</th>
                        <th>{{ __('invoices.amount') }}</th>
                        <th>{{ __('invoices.payment_method') }}</th>
                        <th>{{ __('invoices.added_by') }}</th>
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


@include('invoices.show.edit_invoice')
@include('invoices.show.edit_receipt')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>

    <script src="{{ asset('include_js/invoices.js') }}"></script>
    <script src="{{ asset('include_js/receipts.js') }}"></script>
@endsection





