<div class="modal fade modal-form" id="leave-requests-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('leaves.leave_request_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="leave-requests-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('leaves.leave_type') }} </label>
                        <select id="leave_type_id" name="leave_type" class="form-control"
                                style="width: 100%;"></select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('leaves.start_date') }} </label>
                        <input type="text" name="start_date" placeholder="{{ __('leaves.enter_leave_start_date') }}" id="datepicker"
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('leaves.duration_days') }} </label>
                        <input type="number" name="duration" placeholder="{{ __('leaves.enter_no_of_days') }}"
                               class="form-control">
                    </div>
                </form>

            </div>
            <div class="modal-footer">

                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>


