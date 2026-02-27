@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('members.settings_page_title') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('members') }}" class="btn btn-default">
                        {{ __('members.back_to_members') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <form id="settingsForm" class="form-horizontal">
                    @csrf

                    <div class="row">
                        <div class="col-md-8 col-md-offset-2">

                            {{-- Points System Toggle --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_enabled') }}</label>
                                <div class="col-md-8">
                                    <select name="points_enabled" id="points_enabled" class="form-control">
                                        <option value="1" {{ ($settings['points_enabled'] ?? true) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                                        <option value="0" {{ !($settings['points_enabled'] ?? true) ? 'selected' : '' }}>{{ __('common.no') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('members.points_enabled_hint') }}</small>
                                </div>
                            </div>

                            {{-- Points Expiry Days --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_expiry_days') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="points_expiry_days" id="points_expiry_days" class="form-control" min="0" value="{{ $settings['points_expiry_days'] ?? 0 }}">
                                    <small class="text-muted">{{ __('members.points_expiry_days_hint') }}</small>
                                </div>
                            </div>

                            {{-- Card Number Mode --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.card_number_mode') }}</label>
                                <div class="col-md-8">
                                    <select name="card_number_mode" id="card_number_mode" class="form-control">
                                        <option value="auto" {{ ($settings['card_number_mode'] ?? 'auto') === 'auto' ? 'selected' : '' }}>{{ __('members.card_mode_auto') }}</option>
                                        <option value="phone" {{ ($settings['card_number_mode'] ?? 'auto') === 'phone' ? 'selected' : '' }}>{{ __('members.card_mode_phone') }}</option>
                                        <option value="manual" {{ ($settings['card_number_mode'] ?? 'auto') === 'manual' ? 'selected' : '' }}>{{ __('members.card_mode_manual') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('members.card_number_mode_hint') }}</small>
                                </div>
                            </div>

                            {{-- Referral Bonus Toggle --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.referral_bonus_enabled') }}</label>
                                <div class="col-md-8">
                                    <select name="referral_bonus_enabled" id="referral_bonus_enabled" class="form-control">
                                        <option value="1" {{ ($settings['referral_bonus_enabled'] ?? false) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                                        <option value="0" {{ !($settings['referral_bonus_enabled'] ?? false) ? 'selected' : '' }}>{{ __('common.no') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('members.referral_bonus_enabled_hint') }}</small>
                                </div>
                            </div>

                            {{-- Points Exchange Rate --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_exchange_rate') }}</label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="number" name="points_exchange_rate" id="points_exchange_rate" class="form-control" min="1" value="{{ $settings['points_exchange_rate'] ?? 100 }}">
                                        <span class="input-group-addon">{{ __('members.points') }} = 1</span>
                                    </div>
                                    <small class="text-muted">{{ __('members.points_exchange_rate_hint') }}</small>
                                </div>
                            </div>

                            {{-- Points Exchange Toggle --}}
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_exchange_enabled') }}</label>
                                <div class="col-md-8">
                                    <select name="points_exchange_enabled" id="points_exchange_enabled" class="form-control">
                                        <option value="1" {{ ($settings['points_exchange_enabled'] ?? true) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                                        <option value="0" {{ !($settings['points_exchange_enabled'] ?? true) ? 'selected' : '' }}>{{ __('common.no') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('members.points_exchange_enabled_hint') }}</small>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group">
                                <div class="col-md-8 col-md-offset-4">
                                    <button type="button" class="btn btn-primary" onclick="saveSettings()">
                                        {{ __('common.save') }}
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@endsection
@section('js')
    <script>
        LanguageManager.loadAllFromPHP({
            'members': @json(__('members')),
            'messages': @json(__('messages'))
        });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/member_settings.js') }}"></script>
@endsection
