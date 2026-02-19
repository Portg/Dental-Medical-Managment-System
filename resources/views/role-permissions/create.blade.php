<div class="modal fade modal-form modal-form-lg" id="create_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="modal_title">{{ __('role_permissions.add_new') }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="role_permission_id">
                <div class="form-group">
                    <label>{{ __('role_permissions.role') }} <span class="text-danger">*</span></label>
                    <select class="form-control" id="role_id">
                        <option value="">{{ __('role_permissions.select_role') }}</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>{{ __('role_permissions.permission') }} <span class="text-danger">*</span></label>
                    <select class="form-control" id="permission_id">
                        <option value="">{{ __('role_permissions.select_permission') }}</option>
                        @foreach($permissions as $permission)
                        <option value="{{ $permission->id }}">{{ $permission->name }} ({{ $permission->slug }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="save_role_permission">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>