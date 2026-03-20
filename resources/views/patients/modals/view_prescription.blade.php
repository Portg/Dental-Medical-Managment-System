{{-- View Prescription Detail Modal --}}
<div class="modal fade" id="viewPrescriptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">{{ __('prescriptions.prescription_details') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>{{ __('prescriptions.prescription_no') }}:</strong> <span id="view_rx_no">-</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>{{ __('prescriptions.prescription_date') }}:</strong> <span id="view_rx_date">-</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>{{ __('prescriptions.status') }}:</strong> <span id="view_rx_status">-</span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>{{ __('prescriptions.prescribed_by') }}:</strong> <span id="view_rx_doctor">-</span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>{{ __('prescriptions.notes') }}:</strong> <span id="view_rx_notes">-</span></p>
                    </div>
                    <div class="col-md-4" id="view_rx_invoice_row" style="display:none">
                        <p><strong>{{ __('invoices.invoice_no') }}:</strong> <span id="view_rx_invoice_no">-</span></p>
                    </div>
                </div>

                <hr style="margin:10px 0">

                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('prescriptions.drug_name') }}</th>
                            <th>{{ __('prescriptions.dosage') }}</th>
                            <th>{{ __('prescriptions.quantity') }}</th>
                            <th>{{ __('prescriptions.unit_price') }}</th>
                            <th>{{ __('prescriptions.amount') }}</th>
                            <th>{{ __('prescriptions.frequency') }}</th>
                        </tr>
                    </thead>
                    <tbody id="view_rx_items">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right"><strong>{{ __('prescriptions.total_amount') }}:</strong></td>
                            <td><strong id="view_rx_total">0.00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <a href="#" id="view_rx_print_btn" target="_blank" class="btn btn-info">
                    <i class="fa fa-print"></i> {{ __('prescriptions.print_prescription') }}
                </a>
            </div>
        </div>
    </div>
</div>
