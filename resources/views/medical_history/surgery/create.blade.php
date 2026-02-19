<div class="modal fade modal-form modal-form-lg" id="surgery-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('medical_history.surgery_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="surgery-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="surgery_id" name="surgery_id">
                    <input type="hidden" id="patient_id" name="patient_id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.surgery') }} </label>
                        <input type="text" name="surgery" placeholder="{{ __('medical_history.enter_surgery') }}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.surgery_date') }} </label>
                        <input type="text" id="datepicker" placeholder="yyyy-mm-dd" name="surgery_date" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="text-primary">{{ __('medical_history.notes_optional') }} </label>
                        <textarea class="form-control" name="description" rows="8"></textarea>
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-surgery" onclick="save_surgery()">{{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</div>


