<div class="modal fade modal-form modal-form-lg" id="quotation-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('quotations.quotation_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="quotation-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="quotation_id" name="quotation_id">

                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.procedure') }} </label>
                        <select id="medical_service_id" name="medical_service_id" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.tooth_numbers') }} </label>
                        <input type="text" name="tooth_no" placeholder="{{ __('quotations.enter_tooth_no') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.qty') }} </label>
                        <input type="text" name="qty" id="procedure_qty" placeholder="{{ __('quotations.enter_qty') }}"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.unit_price') }} </label>
                        <input type="text" name="price" id="procedure_price" placeholder="{{ __('quotations.enter_price') }}"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.total_amount') }} </label>
                        <input type="text" name="total_amount" id="total_amount" readonly
                               class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('quotations.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('quotations.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>


