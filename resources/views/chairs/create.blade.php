<div class="modal fade modal-form" id="chair-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('chairs.chairs_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="chair-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="chair_id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('chairs.chair_code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="chair_code" placeholder="{{ __('chairs.enter_chair_code') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('chairs.chair_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="chair_name" placeholder="{{ __('chairs.enter_chair_name') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('chairs.status') }}</label>
                        <select name="status" class="form-control">
                            <option value="active">{{ __('chairs.status_active') }}</option>
                            <option value="maintenance">{{ __('chairs.status_maintenance') }}</option>
                            <option value="offline">{{ __('chairs.status_offline') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('chairs.branch') }}</label>
                        <select name="branch_id" class="form-control">
                            <option value="">{{ __('chairs.select_branch') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('chairs.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
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
