<div class="modal fade" id="packageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="package-modal-title">{{ __('common.add') }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="package-id">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>{{ __('clinical_services.package_name') }} <span class="required">*</span></label>
                            <input type="text" class="form-control" id="package-name" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('clinical_services.package_total_price') }} <span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="package-price">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('clinical_services.package_description') }}</label>
                    <textarea class="form-control" id="package-description" rows="2"></textarea>
                </div>

                <hr>
                <div class="row" style="margin-bottom: 8px;">
                    <div class="col-md-6">
                        <strong>{{ __('clinical_services.package_items') }}</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-sm btn-info" id="btn-add-package-item">
                            <i class="fa fa-plus"></i> {{ __('clinical_services.add_item') }}
                        </button>
                    </div>
                </div>
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{ __('clinical_services.name') }}</th>
                            <th width="80">{{ __('clinical_services.package_item_qty') }}</th>
                            <th width="120">{{ __('clinical_services.package_item_price') }}</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="package-items-body"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save-package">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
