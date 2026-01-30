<div class="modal fade" id="treatment-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('medical_treatment.treatment_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="treatment-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="treatment_id" name="id">
                    <input type="hidden" id="treatment_appointment_id" name="appointment_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_treatment.clinical_notes') }} </label>
                        <textarea name="clinical_notes" rows="8" class="form-control" spellcheck="true"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_treatment.treatment') }} </label>
                        <textarea name="treatment" rows="8" class="form-control" spellcheck="true"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="btn-action" onclick="save_treatment()">{{ __('medical_treatment.save_changes') }}
                </button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('medical_treatment.close') }}</button>
            </div>
        </div>
    </div>
</div>



