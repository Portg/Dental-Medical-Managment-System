<div class="modal fade modal-form" id="create-lab-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.add_lab') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="create-lab-form" autocomplete="off">
                    @csrf
                    <div class="form-group">
                        <label class="text-primary">{{ __('lab_cases.lab_name') }} *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.contact') }}</label>
                                <input type="text" name="contact" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.phone') }}</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('lab_cases.address') }}</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ __('lab_cases.specialties') }}</label>
                                <input type="text" name="specialties" class="form-control" placeholder="{{ __('lab_cases.material_zirconia') }}, {{ __('lab_cases.material_all_ceramic') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.avg_turnaround_days') }}</label>
                                <input type="number" name="avg_turnaround_days" class="form-control" min="1" max="365">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-create-lab" onclick="saveLab()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
