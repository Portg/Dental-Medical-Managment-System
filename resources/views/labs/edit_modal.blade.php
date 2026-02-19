<div class="modal fade modal-form" id="edit-lab-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.edit_lab') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="edit-lab-form" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_lab_id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('lab_cases.lab_name') }} *</label>
                        <input type="text" id="edit_lab_name" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.contact') }}</label>
                                <input type="text" id="edit_lab_contact" name="contact" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.phone') }}</label>
                                <input type="text" id="edit_lab_phone" name="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('lab_cases.address') }}</label>
                        <input type="text" id="edit_lab_address" name="address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ __('lab_cases.specialties') }}</label>
                                <input type="text" id="edit_lab_specialties" name="specialties" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.avg_turnaround_days') }}</label>
                                <input type="number" id="edit_lab_avg_turnaround_days" name="avg_turnaround_days" class="form-control" min="1" max="365">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit_lab_is_active" name="is_active" value="1">
                            {{ __('lab_cases.is_active') }}
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-update-lab" onclick="updateLab()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
