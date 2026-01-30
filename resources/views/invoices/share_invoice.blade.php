<div class="modal fade" id="share-invoice-modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h3 class="modal-title">{{ __('invoices.share_invoice_on_email') }}</h3>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="share-invoice-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" value="" name="invoice_id"/>
                    <div class="form-body">
                        <div class="form-group">
                            <label class="control-label col-md-3">{{ __('invoices.invoice_no') }}</label>
                            <div class="col-md-9">
                                <input name="invoice_no" placeholder="{{ __('invoices.enter_invoice_no_here') }}" readonly class="form-control"
                                       type="text">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3"> {{ __('invoices.patient_name') }}</label>
                            <div class="col-md-9">
                                <input name="name" readonly="" placeholder="{{ __('invoices.enter_patient_name_here') }}" class="form-control" type="text">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3">{{ __('invoices.email_address') }}</label>
                            <div class="col-md-9">
                                <input name="email" placeholder="{{ __('invoices.enter_email_address_here') }}" autocomplete="off" class="form-control"
                                       type="email" required="">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-md-3">{{ __('invoices.message_optional') }}</label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="message" rows="5"
                                          placeholder="{{ __('invoices.please_enter_custom_message_here') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-share" onclick="sendInvoice()" class="btn btn-primary">{{ __('invoices.share_invoice') }}
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">{{ __('common.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
