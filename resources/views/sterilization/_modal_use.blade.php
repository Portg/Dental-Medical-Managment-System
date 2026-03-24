<div class="modal fade" id="useModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('sterilization.log_use') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="use-record-id">

                {{-- 只读展示批次信息 --}}
                <div class="alert alert-info p-2 mb-3" id="use-record-info">
                    批次号：<strong id="use-batch-no"></strong>
                    &nbsp;|&nbsp; 器械包：<strong id="use-kit-name"></strong>
                </div>

                <div class="form-group">
                    <label>{{ __('sterilization.used_at') }} *</label>
                    <input type="datetime-local" class="form-control" id="use-used-at">
                </div>

                {{-- 搜索关联患者（Select2 AJAX） --}}
                <div class="form-group">
                    <label>关联患者（可选）</label>
                    <select class="form-control select2-patient" id="use-patient-id">
                        <option value="">不关联患者</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>{{ __('sterilization.usage_notes') }}</label>
                    <textarea class="form-control" id="use-notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-success" id="btn-confirm-use">确认登记</button>
            </div>
        </div>
    </div>
</div>
