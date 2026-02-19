<div class="modal fade modal-form modal-form-lg" id="editLevelModal" tabindex="-1" role="dialog" aria-labelledby="editLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editLevelModalLabel">{{ __('members.edit_level') }}</h4>
            </div>
            <div class="modal-body">
                <form id="editLevelForm" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="level_id" id="edit_level_id">
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.level_name') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <input type="text" name="name" id="edit_level_name" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.level_code') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <input type="text" name="code" id="edit_level_code" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.color') }}</label>
                                <div class="col-md-8">
                                    <input type="color" name="color" id="edit_level_color" class="form-control" value="#999999">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.discount_rate') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="number" name="discount_rate" id="edit_level_discount_rate" class="form-control" step="0.01" min="0" max="100" required>
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <small class="text-muted">{{ __('members.discount_rate_hint') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.min_consumption') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="min_consumption" id="edit_level_min_consumption" class="form-control" step="0.01" min="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_rate') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="points_rate" id="edit_level_points_rate" class="form-control" step="0.01" min="0">
                                    <small class="text-muted">{{ __('members.points_rate_hint') }}</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.sort_order') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="sort_order" id="edit_level_sort_order" class="form-control" min="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.is_active') }}</label>
                                <div class="col-md-8">
                                    <select name="is_active" id="edit_level_is_active" class="form-control">
                                        <option value="1">{{ __('common.yes') }}</option>
                                        <option value="0">{{ __('common.no') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-2 text-primary">{{ __('members.benefits') }}</label>
                        <div class="col-md-10">
                            <textarea name="benefits" id="edit_level_benefits" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="updateLevel()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
