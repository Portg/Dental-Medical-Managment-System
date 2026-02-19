<div class="modal fade modal-form modal-form-lg" id="chronic-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('medical_history.illness_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="chronic-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="illness_id" name="illness_id">
                    <input type="hidden" id="chronic_patient_id" name="patient_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.chronic_disease') }} </label>
                        <input type="text" name="disease" placeholder="{{ __('medical_history.enter_disease') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.disease_status') }} </label><br>
                        <input type="radio" name="status" value="Active">{{ __('medical_history.active') }}<br>
                        <input type="radio" name="status" value="Treated">{{ __('medical_history.treated') }}<br>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-chronic" onclick="save_illness()">{{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>


