<div class="modal fade" id="kitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kit-modal-title">新增器械包</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="kit-id">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>{{ __('sterilization.kit_no') }} *</label>
                        <input type="text" class="form-control" id="kit-no" placeholder="KIT-001">
                    </div>
                    <div class="form-group col-md-8">
                        <label>{{ __('sterilization.kit_name') }} *</label>
                        <input type="text" class="form-control" id="kit-name">
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <strong>{{ __('sterilization.instruments') }}</strong>
                    <button type="button" class="btn btn-xs btn-outline-primary" id="btn-add-instrument">
                        + 添加器械
                    </button>
                </div>
                <table class="table table-sm" id="instruments-table">
                    <thead>
                        <tr>
                            <th>器械名称</th>
                            <th width="100">数量</th>
                            <th width="60"></th>
                        </tr>
                    </thead>
                    <tbody id="instruments-body">
                        {{-- JS 动态添加行 --}}
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-kit">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
