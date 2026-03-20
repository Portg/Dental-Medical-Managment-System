{{-- Create / Edit Prescription Modal --}}
<div class="modal fade" id="addPrescriptionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="patientPrescriptionForm">
                @csrf
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                <input type="hidden" id="rx_edit_id" value="">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" id="prescriptionModalTitle">{{ __('prescriptions.create_prescription') }}</h4>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('prescriptions.prescribed_by') }}</label>
                                <select name="doctor_id" id="rx_doctor_id" class="form-control">
                                    @foreach($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ $doc->id == auth()->id() ? 'selected' : '' }}>
                                            {{ $doc->surname . $doc->othername }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('prescriptions.prescription_date') }}</label>
                                <input type="date" name="prescription_date" id="rx_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('prescriptions.notes') }}</label>
                                <input type="text" name="notes" id="rx_notes" class="form-control" placeholder="{{ __('prescriptions.notes') }}">
                            </div>
                        </div>
                    </div>

                    <hr style="margin:8px 0">

                    <div class="row" style="margin-bottom:8px">
                        <div class="col-md-6">
                            <strong>{{ __('prescriptions.medications') }}</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-xs btn-default" onclick="addRxItemRow()">
                                <i class="fa fa-plus"></i> {{ __('prescriptions.add_medication') }}
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed" id="rxItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th style="width:35%">{{ __('prescriptions.select_prescription_service') }}</th>
                                    <th style="width:60px">{{ __('prescriptions.quantity') }}</th>
                                    <th style="width:80px">{{ __('prescriptions.unit_price') }}</th>
                                    <th style="width:80px">{{ __('prescriptions.amount') }}</th>
                                    <th>{{ __('prescriptions.dosage') }}</th>
                                    <th>{{ __('prescriptions.frequency') }}</th>
                                    <th style="width:30px"></th>
                                </tr>
                            </thead>
                            <tbody id="rxItemRows">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>{{ __('prescriptions.total_amount') }}:</strong></td>
                                    <td><strong id="rxTotalAmount">0.00</strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-rx-save" onclick="savePatientPrescription(false)">
                        {{ __('prescriptions.save_only') }}
                    </button>
                    <button type="button" class="btn btn-success" id="btn-rx-settle" onclick="savePatientPrescription(true)">
                        <i class="fa fa-check"></i> {{ __('prescriptions.save_and_settle') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
