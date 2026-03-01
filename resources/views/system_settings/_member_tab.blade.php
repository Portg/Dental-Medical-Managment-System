<form id="memberSettingsForm" class="form-horizontal ss-form">
    @csrf
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_points_enabled') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="points_enabled" value="0">
                        <input type="checkbox" name="points_enabled" value="1"
                               {{ ($member['points_enabled'] ?? true) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.member_points_enabled_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_points_expiry_days') }}</label>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" name="points_expiry_days" class="form-control" min="0"
                               value="{{ $member['points_expiry_days'] ?? 0 }}">
                        <span class="input-group-addon">{{ __('system_settings.days') }}</span>
                    </div>
                    <small class="text-muted">{{ __('system_settings.member_points_expiry_days_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_card_number_mode') }}</label>
                <div class="col-md-4">
                    <select name="card_number_mode" class="form-control">
                        <option value="auto" {{ ($member['card_number_mode'] ?? 'auto') === 'auto' ? 'selected' : '' }}>{{ __('system_settings.member_card_mode_auto') }}</option>
                        <option value="phone" {{ ($member['card_number_mode'] ?? 'auto') === 'phone' ? 'selected' : '' }}>{{ __('system_settings.member_card_mode_phone') }}</option>
                        <option value="manual" {{ ($member['card_number_mode'] ?? 'auto') === 'manual' ? 'selected' : '' }}>{{ __('system_settings.member_card_mode_manual') }}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_referral_bonus_enabled') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="referral_bonus_enabled" value="0">
                        <input type="checkbox" name="referral_bonus_enabled" value="1"
                               {{ ($member['referral_bonus_enabled'] ?? false) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.member_referral_bonus_enabled_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_points_exchange_rate') }}</label>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" name="points_exchange_rate" class="form-control" min="1"
                               value="{{ $member['points_exchange_rate'] ?? 100 }}">
                        <span class="input-group-addon">= 1{{ __('common.yuan') }}</span>
                    </div>
                    <small class="text-muted">{{ __('system_settings.member_points_exchange_rate_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.member_points_exchange_enabled') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="points_exchange_enabled" value="0">
                        <input type="checkbox" name="points_exchange_enabled" value="1"
                               {{ ($member['points_exchange_enabled'] ?? true) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.member_points_exchange_enabled_hint') }}</small>
                </div>
            </div>

            <hr>
            <div class="form-group">
                <div class="col-md-8 col-md-offset-4">
                    <button type="button" class="btn btn-primary" onclick="saveSettings('member')">
                        <i class="fa fa-check"></i> {{ __('common.save') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>
