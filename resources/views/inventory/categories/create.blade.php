<div class="modal fade" id="category-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">{{ __('inventory.add_category') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="category-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.category_code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="code" placeholder="{{ __('inventory.category_code') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.category_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" placeholder="{{ __('inventory.category_name') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.category_type') }} <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="">{{ __('inventory.select_type') }}</option>
                            <option value="drug">{{ __('inventory.type_drug') }}</option>
                            <option value="consumable">{{ __('inventory.type_consumable') }}</option>
                            <option value="instrument">{{ __('inventory.type_instrument') }}</option>
                            <option value="dental_material">{{ __('inventory.type_dental_material') }}</option>
                            <option value="office">{{ __('inventory.type_office') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.description') }}</label>
                        <textarea name="description" placeholder="{{ __('inventory.description') }}" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('inventory.sort_order') }}</label>
                        <input type="number" name="sort_order" placeholder="0" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="mt-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked> {{ __('common.active') }}
                            <span></span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
