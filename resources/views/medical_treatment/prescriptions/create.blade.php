<div class="modal fade modal-form modal-form-lg" id="prescription-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('medical.prescription') }} </h4>
            </div>
            <div class="modal-body">

                <form action="#" id="prescription-form">

                    @csrf
                    <input type="hidden" name="appointment_id" id="prescription_appointment_id">
                    <table class="table table-bordered" id="prescriptionsTable">
                        <tr>
                            <th class="text-primary">{{ __('medical.drug_name') }}</th>
                            <th class="text-primary">{{ __('medical.dosage') }}</th>
                            <th class="text-primary">{{ __('medical.instructions') }}</th>
                            <th class="text-primary">{{ __('common.action') }}</th>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" id="drug_name" class="form-control" name="addmore[0][drug]"
                                       placeholder="{{ __('medical.drug_name') }}"/>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="addmore[0][qty]"
                                       placeholder="ml/mg"/>
                            </td>
                            <td>
                                <textarea class="form-control" name="addmore[0][directions]"></textarea>
                            </td>
                            <td>
                                <button type="button" name="add" id="add" class="btn btn-info">{{ __('common.add') }}</button>
                            </td>
                        </tr>

                    </table>
                    <br><br>

                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-success" id="btn-save" onclick="save_prescription()">{{ __('common.save') }} {{ __('medical.prescription') }}
                </button>
            </div>
        </div>
    </div>
</div>


