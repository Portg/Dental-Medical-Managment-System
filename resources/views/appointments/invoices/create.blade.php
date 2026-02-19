<div class="modal fade modal-form" id="New-invoice-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('invoices.invoice_form') }}</h4>
            </div>
            <div class="modal-body">

                <form action="#" id="New-invoice-form">

                    @csrf
                    <input type="hidden" name="appointment_id" id="invoicing_appointment_id">
                    <table class="table table-bordered" id="InvoicesTable">
                        <tr>
                            <th class="text-primary">{{ __('invoices.procedure') }}</th>
                            <th class="text-primary">{{ __('invoices.tooth_numbers') }}<span class="text-danger">({{ __('common.optional') }})</span></th>
                            <th class="text-primary">{{ __('invoices.quantity') }}</th>
                            <th class="text-primary">{{ __('invoices.unit_price') }}</th>
                            <th class="text-primary">{{ __('invoices.total_amount') }}</th>
                            <th class="text-primary">{{ __('invoices.choose_doctor') }}</th>
                            <th class="text-primary">{{ __('common.action') }}</th>
                        </tr>
                        <tr>
                            <td>

                                <select id="service" name="addmore[0][medical_service_id]" class="form-control"
                                        style="width: 100%;"></select>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="addmore[0][tooth_no]"
                                       placeholder="{{ __('invoices.enter_tooth_no') }}"/>
                            </td>
                            <td>
                                <input type="number" id="procedure_qty" class="form-control" name="addmore[0][qty]"
                                       placeholder="{{ __('invoices.enter_quantity') }}"/>
                            </td>
                            <td>
                                <input type="number" id="procedure_price" class="form-control" name="addmore[0][price]"
                                       placeholder="{{ __('invoices.enter_unit_price') }}"/>
                            </td>
                            <td>
                                <input type="text" id="total_amount" class="form-control" readonly/>
                            </td>
                            <td>

                                <select id="doctor_id" name="addmore[0][doctor_id]" class="form-control"
                                        style="width: 100%;"></select>
                            </td>
                            <td>
                                <button type="button" name="add" id="addInvoiceItem" class="btn btn-info">{{ __('invoices.add_more') }}
                                </button>
                            </td>
                        </tr>

                    </table>
                    <br><br>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-success" id="btnSave" onclick="save_invoice()">{{ __('invoices.generate_invoice') }}
                </button>
            </div>
        </div>
    </div>
</div>


