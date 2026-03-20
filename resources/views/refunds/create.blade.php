@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-8">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-wallet font-dark"></i>
                            <span class="caption-subject font-dark bold uppercase">{{ __('invoices.new_refund') }}</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <form id="refund_form">
                            @csrf
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i>
                                {{ __('invoices.refund_approval_info', ['amount' => 100]) }}
                            </div>

                            <div class="form-group">
                                <label>{{ __('invoices.select_invoice') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="invoice_id" name="invoice_id" style="width:100%">
                                    <option value="">{{ __('invoices.search_invoice') }}</option>
                                    @if(isset($invoice))
                                        <option value="{{ $invoice->id }}" selected>
                                            {{ $invoice->invoice_no }} - {{ $invoice->patient ? $invoice->patient->full_name : '' }}
                                        </option>
                                    @endif
                                </select>
                            </div>

                            <div id="invoice_info" style="display: none;">
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>{{ __('invoices.invoice_no') }}:</strong> <span id="info_invoice_no"></span></p>
                                            <p><strong>{{ __('patient.name') }}:</strong> <span id="info_patient_name"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>{{ __('invoices.paid_amount') }}:</strong> <span id="info_paid_amount" class="text-success"></span></p>
                                            <p><strong>{{ __('invoices.refunded_amount') }}:</strong> <span id="info_refunded_amount" class="text-danger"></span></p>
                                            <p><strong>{{ __('invoices.max_refundable') }}:</strong> <span id="info_max_refundable" class="text-primary font-bold"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('invoices.refund_amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" class="form-control" id="refund_amount" name="refund_amount" step="0.01" min="0.01">
                                </div>
                                <span class="help-block" id="amount_warning" style="display:none; color:orange;">
                                    <i class="fa fa-exclamation-triangle"></i> {{ __('invoices.refund_needs_approval') }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>{{ __('invoices.refund_method') }} <span class="text-danger">*</span></label>
                                <select class="form-control" id="refund_method" name="refund_method">
                                    <option value="">{{ __('common.please_select') }}</option>
                                    <option value="cash">{{ __('invoices.cash') }}</option>
                                    <option value="wechat">{{ __('invoices.wechat_pay') }}</option>
                                    <option value="alipay">{{ __('invoices.alipay') }}</option>
                                    <option value="card">{{ __('invoices.bank_card') }}</option>
                                    <option value="stored_value">{{ __('invoices.stored_value') }}</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ __('invoices.refund_reason') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="refund_reason" name="refund_reason" rows="3" placeholder="{{ __('invoices.enter_refund_reason') }}"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('invoices.submit_refund') }}
                                </button>
                                <a href="{{ url('refunds') }}" class="btn btn-default">{{ __('common.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="icon-info font-dark"></i>
                            <span class="caption-subject font-dark bold">{{ __('invoices.refund_rules') }}</span>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> {{ __('invoices.rule_refund_100') }}</li>
                            <li><i class="fa fa-clock-o text-warning"></i> {{ __('invoices.rule_refund_above_100') }}</li>
                            <li><i class="fa fa-ban text-danger"></i> {{ __('invoices.rule_no_double_refund') }}</li>
                            <li><i class="fa fa-credit-card text-info"></i> {{ __('invoices.rule_stored_value_refund') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}"></script>
<script>
LanguageManager.loadFromPHP(@json(__('invoices')), 'invoices');
window.RefundsCreateConfig = {
    apiSearchUrl:        '{{ url('api/invoices/search') }}',
    refundableAmountUrl: '{{ url('refunds/refundable-amount') }}',
    refundsUrl:          '{{ url('refunds') }}',
    pendingApprovalsUrl: '{{ url('refunds/pending-approvals') }}',
    preloadInvoiceId:    {!! isset($invoice) ? $invoice->id : 'null' !!}
};
</script>
<script src="{{ asset('include_js/refunds_create.js') }}?v={{ filemtime(public_path('include_js/refunds_create.js')) }}"></script>
@endsection
