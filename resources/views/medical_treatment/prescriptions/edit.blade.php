<div class="modal fade modal-form modal-form-lg" id="edit-prescription-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('medical_treatment.prescriptions_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="edit-prescription-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="prescription_id" name="prescription_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_treatment.drug') }} </label>
                        <input type="text" name="drug" placeholder="{{ __('medical_treatment.enter_drug') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_treatment.qty') }} </label>
                        <input type="text" name="qty" placeholder="{{ __('medical_treatment.enter_qty') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_treatment.directions') }} </label>
                        <textarea name="directions" class="form-control"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="update_prescription_record()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>


