<div class="modal fade modal-form" id="quotation-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('quotations.quotation_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="quotation-form">

                    @csrf
                    <div class="form-group">
                        <label class="text-primary">{{ __('quotations.patient') }}</label>
                        <select id="patient" name="patient_id" class="form-control" style="width: 100%;"></select>
                    </div>
                    <br>

                    <table class="table table-bordered" id="QuotationItemsTable">
                        <tr>
                            <th class="text-primary">{{ __('quotations.procedure') }}</th>
                            <th class="text-primary">{{ __('quotations.tooth_numbers') }}<span class="text-danger">{{ __('quotations.optional') }}</span></th>
                            <th class="text-primary">{{ __('quotations.qty') }}</th>
                            <th class="text-primary">{{ __('quotations.unit_price') }}</th>
                            <th class="text-primary">{{ __('quotations.total_amount') }}</th>
                            <th class="text-primary">{{ __('quotations.action') }}</th>
                        </tr>
                        <tr>
                            <td>
                                <select id="service" name="addmore[0][medical_service_id]" class="form-control"
                                        style="width: 100%;"></select>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="addmore[0][tooth_no]"
                                       placeholder="{{ __('quotations.enter_tooth_no') }}"/>
                            </td>
                            <td>
                                <input type="number" id="procedure_qty" class="form-control" name="addmore[0][qty]"
                                       placeholder="{{ __('quotations.enter_qty') }}"/>
                            </td>
                            <td>
                                <input type="number" id="procedure_price" class="form-control" name="addmore[0][price]"
                                       placeholder="{{ __('quotations.enter_price') }}"/>
                            </td>
                            <td>
                                <input type="text" readonly="" id="total_amount" class="form-control"
                                       placeholder=""/>
                            </td>
                            <td>
                                <button type="button" name="add" id="addQuotationItem" class="btn btn-info">{{ __('quotations.add_more') }}
                                </button>
                            </td>
                        </tr>

                    </table>
                    <br><br>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('quotations.close') }}</button>
                <button type="button" class="btn btn-success" id="btnSave" onclick="save_quotation()">{{ __('quotations.generate_quotation') }}
                </button>
            </div>
        </div>
    </div>
</div>


