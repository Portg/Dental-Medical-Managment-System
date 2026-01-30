<div class="modal fade" id="phrase-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">{{ __('templates.create_phrase') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"></div>
                <form action="#" id="phrase-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="phrase_id" name="id">
                    <div class="form-body">
                        <div class="form-group">
                            <label class="control-label col-md-4 text-primary">{{ __('templates.shortcut') }} <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                                <input type="text" name="shortcut" class="form-control" required maxlength="20" placeholder="{{ __('templates.shortcut_hint') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4 text-primary">{{ __('templates.phrase') }} <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                                <input type="text" name="phrase" class="form-control" required placeholder="{{ __('templates.phrase_hint') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4 text-primary">{{ __('templates.category') }}</label>
                            <div class="col-md-8">
                                <select name="category" class="form-control">
                                    <option value="">{{ __('common.select') }}</option>
                                    <option value="examination">{{ __('templates.examination') }}</option>
                                    <option value="diagnosis">{{ __('templates.diagnosis') }}</option>
                                    <option value="treatment">{{ __('templates.treatment') }}</option>
                                    <option value="other">{{ __('templates.other') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4 text-primary">{{ __('templates.scope') }} <span class="text-danger">*</span></label>
                            <div class="col-md-8">
                                <select name="scope" class="form-control" required>
                                    <option value="system">{{ __('templates.system') }}</option>
                                    <option value="personal">{{ __('templates.personal') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-4 text-primary">{{ __('common.status') }}</label>
                            <div class="col-md-8">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_active" value="1" checked> {{ __('common.active') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-save" class="btn green" onclick="save_phrase()">{{ __('common.save_record') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
