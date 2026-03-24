<div class="modal fade" id="batchPriceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('clinical_services.batch_update_price') }}</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    {{ __('clinical_services.batch_price_scope_hint') }}
                </p>
                <div class="form-group">
                    <label>{{ __('clinical_services.batch_mode') }}</label>
                    <div>
                        <label class="radio-inline">
                            <input type="radio" name="batch-mode" value="percent" checked>
                            {{ __('clinical_services.batch_mode_percent') }}
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="batch-mode" value="fixed">
                            {{ __('clinical_services.batch_mode_fixed') }}
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('clinical_services.batch_value') }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" id="batch-price-value"
                               placeholder="{{ __('clinical_services.batch_value_placeholder') }}">
                        <span class="input-group-addon" id="batch-unit-label">%</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-warning" id="btn-confirm-batch-price">{{ __('clinical_services.confirm_price_change') }}</button>
            </div>
        </div>
    </div>
</div>
