<div class="modal fade modal-form modal-form-lg sterilization-modal" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="record-modal-title">新增灭菌记录</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="record-id">
                <div class="row">
                    <div class="form-group col-md-7">
                        <label>{{ __('sterilization.kit_name') }} *</label>
                        <select class="form-control select2" id="record-kit-id">
                            {{-- JS 动态注入 sterilizationKits --}}
                        </select>
                    </div>
                    <div class="form-group col-md-5">
                        <label>{{ __('sterilization.method') }} *</label>
                        <select class="form-control" id="record-method">
                            <option value="autoclave">{{ __('sterilization.method_autoclave') }}</option>
                            <option value="chemical">{{ __('sterilization.method_chemical') }}</option>
                            <option value="dry_heat">{{ __('sterilization.method_dry_heat') }}</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label>{{ __('sterilization.temperature') }}</label>
                        <input type="number" step="0.1" class="form-control" id="record-temperature">
                    </div>
                    <div class="form-group col-md-6">
                        <label>{{ __('sterilization.duration_minutes') }}</label>
                        <input type="number" class="form-control" id="record-duration">
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('sterilization.sterilized_at') }} *</label>
                    <input type="datetime-local" class="form-control" id="record-sterilized-at">
                </div>
                <div class="form-group">
                    <label>备注</label>
                    <textarea class="form-control" id="record-notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-record">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
