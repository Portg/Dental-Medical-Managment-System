<div class="modal fade modal-form" id="item-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('inventory.add_item') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="item-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.item_code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="item_code" placeholder="{{ __('inventory.item_code') }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.item_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" placeholder="{{ __('inventory.item_name') }}" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.specification') }}</label>
                                <input type="text" name="specification" placeholder="{{ __('inventory.specification') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.unit') }} <span class="text-danger">*</span></label>
                                <input type="text" name="unit" placeholder="{{ __('inventory.unit') }}" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.category') }} <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">{{ __('inventory.select_category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.brand') }}</label>
                                <input type="text" name="brand" placeholder="{{ __('inventory.brand') }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.reference_price') }}</label>
                                <input type="number" step="0.01" name="reference_price" placeholder="0.00" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.selling_price') }}</label>
                                <input type="number" step="0.01" name="selling_price" placeholder="0.00" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.stock_warning_level') }}</label>
                                <input type="number" name="stock_warning_level" placeholder="10" class="form-control" value="10">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('inventory.storage_location') }}</label>
                                <input type="text" name="storage_location" placeholder="{{ __('inventory.storage_location') }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="mt-checkbox mt-checkbox-outline">
                                    <input type="checkbox" name="track_expiry" value="1"> {{ __('inventory.track_expiry') }}
                                    <span></span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="mt-checkbox mt-checkbox-outline">
                                    <input type="checkbox" name="is_active" value="1" checked> {{ __('common.active') }}
                                    <span></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.notes') }}</label>
                        <textarea name="notes" placeholder="{{ __('inventory.notes') }}" class="form-control" rows="2"></textarea>
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
