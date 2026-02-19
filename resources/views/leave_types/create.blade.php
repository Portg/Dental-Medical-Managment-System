<div class="modal fade modal-form" id="leave-types-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"> {{ __('leaves.leave_type_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="leave-types-form" autocomplete="off">

                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('leaves.leave_type') }} </label>
                        <input type="text" name="name" placeholder="{{ __('leaves.enter_leave_type') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('leaves.maximum_leave_days') }} </label>
                        <input type="text" name="max_days" placeholder="{{ __('leaves.enter_no_of_days') }}" class="form-control">
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


