<div class="modal fade modal-form modal-form-lg" id="Allergy-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('medical_history.allergies_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="Allergy-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="allergy_id" name="allergy_id">
                    <input type="hidden" id="allergy_patient_id" name="patient_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.allergy') }} </label>
                        <textarea class="form-control" rows="5" name="body_reaction"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-allergy" onclick="save_allergy()">{{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>


