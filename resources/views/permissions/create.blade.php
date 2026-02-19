<div class="modal fade modal-form modal-form-lg" id="create_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="modal_title">{{ __('permissions.add_permission') }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="permission_id">
                <div class="form-group">
                    <label>{{ __('permissions.name') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" placeholder="{{ __('permissions.enter_permission_name') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('permissions.slug') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="slug" placeholder="{{ __('permissions.enter_slug') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('permissions.module') }}</label>
                    <input type="text" class="form-control" id="module" placeholder="{{ __('permissions.enter_module_name') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('permissions.description') }}</label>
                    <textarea class="form-control" id="description" rows="3" placeholder="{{ __('permissions.enter_description') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="save_permission">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>