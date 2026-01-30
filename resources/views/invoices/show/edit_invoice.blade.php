<div class="modal fade" id="invoice-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('invoices.invoice_item_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="invoice-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="invoice_item_id" name="invoice_item_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.procedure') }} </label>
                        <select id="medical_service_id" name="medical_service_id" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.procedure_done_by') }}</label>
                        <select id="doctor_id" name="doctor_id" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.tooth_numbers_optional') }} </label>
                        <input type="text" name="tooth_no" placeholder="{{ __('invoices.enter_tooth_no') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.qty') }} </label>
                        <input type="number" id="procedure_qty" name="qty" placeholder="{{ __('invoices.enter_qty_here') }}"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.unit_price') }}</label>
                        <input type="number" id="procedure_price" name="price" placeholder="{{ __('invoices.enter_price_here') }}"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('invoices.total_price') }}</label>
                        <input type="text" name="total_amount" id="total_amount" placeholder="" readonly
                               class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_invoice_update()">{{ __('common.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>


