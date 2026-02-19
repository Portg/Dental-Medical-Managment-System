<div class="modal fade modal-form modal-form-lg" id="commission-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('commission_rules.rule_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="commission-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('commission_rules.rule_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="rule_name" class="form-control" required maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('commission_rules.mode') }} <span class="text-danger">*</span></label>
                                <select name="commission_mode" class="form-control" required onchange="toggleMode(this.value)">
                                    <option value="">{{ __('commission_rules.select_mode') }}</option>
                                    <option value="fixed_percentage">{{ __('commission_rules.mode_fixed_percentage') }}</option>
                                    <option value="tiered">{{ __('commission_rules.mode_tiered') }}</option>
                                    <option value="fixed_amount">{{ __('commission_rules.mode_fixed_amount') }}</option>
                                    <option value="mixed">{{ __('commission_rules.mode_mixed') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('commission_rules.service_type') }}</label>
                                <input type="text" name="target_service_type" class="form-control" placeholder="{{ __('commission_rules.service_type_placeholder') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('commission_rules.specific_service') }}</label>
                                <select name="medical_service_id" class="form-control">
                                    <option value="">{{ __('commission_rules.all_services') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Percentage Options -->
                    <div id="fixed_percentage_options" class="mode-options" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.base_rate') }} (%)</label>
                                    <input type="number" name="base_commission_rate" class="form-control" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Amount Options -->
                    <div id="fixed_amount_options" class="mode-options" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.bonus_amount') }}</label>
                                    <input type="number" name="bonus_amount" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tiered Options -->
                    <div id="tiered_options" class="mode-options" style="display: none;">
                        <h5>{{ __('commission_rules.tier_settings') }}</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier1_threshold') }}</label>
                                    <input type="number" name="tier1_threshold" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier1_rate') }} (%)</label>
                                    <input type="number" name="tier1_rate" class="form-control" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier2_threshold') }}</label>
                                    <input type="number" name="tier2_threshold" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier2_rate') }} (%)</label>
                                    <input type="number" name="tier2_rate" class="form-control" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier3_threshold') }}</label>
                                    <input type="number" name="tier3_threshold" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('commission_rules.tier3_rate') }} (%)</label>
                                    <input type="number" name="tier3_rate" class="form-control" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('commission_rules.branch') }}</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">{{ __('commission_rules.all_branches') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('common.status') }}</label><br>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    {{ __('common.active') }}
                                </label>
                            </div>
                        </div>
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
