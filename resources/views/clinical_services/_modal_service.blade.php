<div class="modal fade service-form-modal" id="serviceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog service-form-modal__dialog">
        <div class="modal-content">
            <div class="modal-header service-form-modal__header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="service-modal-title">{{ __('common.add') }}</h4>
            </div>
            <div class="modal-body service-form-modal__body">
                <input type="hidden" id="service-id">
                <div class="form-group service-form-group">
                    <label>{{ __('clinical_services.name') }} <span class="required">*</span></label>
                    <input type="text" class="form-control" id="service-name" maxlength="255">
                </div>
                <div class="row service-form-row">
                    <div class="col-md-6">
                        <div class="form-group service-form-group">
                            <label>{{ __('clinical_services.price') }} <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="service-price">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group service-form-group">
                            <label>{{ __('clinical_services.unit') }}</label>
                            <input type="text" class="form-control" id="service-unit" maxlength="20" placeholder="次">
                        </div>
                    </div>
                </div>
                <div class="form-group service-form-group">
                    <label>{{ __('clinical_services.service_categories') }}</label>
                    <select class="form-control select2" id="service-category-id" style="width:100%;">
                        <option value="">-- {{ __('clinical_services.service_categories') }} --</option>
                    </select>
                </div>
                <div class="form-group service-form-group">
                    <label>{{ __('clinical_services.description') }}</label>
                    <textarea class="form-control" id="service-description" rows="2" maxlength="500"></textarea>
                </div>
                <div class="service-form-toggles">
                    <div class="service-toggle-item">
                        <label class="mt-checkbox service-toggle-label">
                                <input type="checkbox" id="service-is-discountable" checked>
                                {{ __('clinical_services.is_discountable') }}
                                <span></span>
                        </label>
                    </div>
                    <div class="service-toggle-item">
                        <label class="mt-checkbox service-toggle-label">
                                <input type="checkbox" id="service-is-favorite">
                                {{ __('clinical_services.is_favorite') }}
                                <span></span>
                        </label>
                    </div>
                    <div class="service-toggle-item">
                        <label class="mt-checkbox service-toggle-label">
                                <input type="checkbox" id="service-is-active" checked>
                                {{ __('common.active') }}
                                <span></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer service-form-modal__footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-service">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
