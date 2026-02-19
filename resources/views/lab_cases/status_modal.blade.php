<div class="modal fade modal-form" id="status-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.update_status') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="status-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="status_case_id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('lab_cases.status') }} *</label>
                        <select id="status_value" name="status" class="form-control">
                            <option value="">--</option>
                            @foreach(\App\LabCase::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ __('lab_cases.status_' . $key) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="rework_reason_group" style="display:none">
                        <label class="text-primary">{{ __('lab_cases.rework_reason') }} *</label>
                        <textarea id="status_rework_reason" name="rework_reason" class="form-control" rows="3"
                                  placeholder="{{ __('lab_cases.enter_rework_reason') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-status" onclick="saveStatus()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
