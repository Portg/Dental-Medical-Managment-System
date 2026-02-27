@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/list-page.css') }}">
    <link rel="stylesheet" href="{{ asset('css/form-modal.css') }}">
    <style>
        .tabbable-line > .nav-tabs {
            border-bottom: 2px solid #ebeef5;
            margin-bottom: 0;
        }
        .tabbable-line > .nav-tabs > li > a {
            color: #606266;
            font-size: 14px;
            padding: 10px 20px;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: color 0.2s, border-color 0.2s;
        }
        .tabbable-line > .nav-tabs > li > a:hover {
            color: #00838f;
            background: transparent;
            border: none;
            border-bottom-color: #b2ebf2;
        }
        .tabbable-line > .nav-tabs > li.active > a,
        .tabbable-line > .nav-tabs > li.active > a:hover,
        .tabbable-line > .nav-tabs > li.active > a:focus {
            color: #00838f;
            background: transparent;
            border: none;
            border-bottom: 2px solid #00838f;
        }
        .tab-pane { padding: 20px 0; }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('members.member_settings_title') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('members') }}" class="btn btn-default">
                        {{ __('members.back_to_members') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#tab_levels" data-toggle="tab">{{ __('members.tab_levels') }}</a>
                        </li>
                        <li>
                            <a href="#tab_card_number" data-toggle="tab">{{ __('members.tab_card_number') }}</a>
                        </li>
                        <li>
                            <a href="#tab_points_rules" data-toggle="tab">{{ __('members.tab_points_rules') }}</a>
                        </li>
                        <li>
                            <a href="#tab_referral" data-toggle="tab">{{ __('members.tab_referral') }}</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        {{-- Tab 1: 等级设置 --}}
                        <div class="tab-pane active" id="tab_levels">
                            @if($upgradeLevels->count() > 0)
                            <div style="background:#f8f9fa; border-radius:6px; padding:12px 16px; margin-bottom:15px;">
                                <small class="text-muted" style="display:block; margin-bottom:8px;"><i class="fa fa-arrow-up"></i> {{ __('members.upgrade_path') }}</small>
                                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:4px;">
                                    <span class="label label-default" style="font-size:12px;">{{ __('members.new_member') }}</span>
                                    @foreach($upgradeLevels as $ul)
                                        <i class="fa fa-long-arrow-right text-muted"></i>
                                        <span class="label" style="background:{{ $ul->color }}; font-size:12px;">
                                            {{ $ul->name }}
                                            <small>(&ge; {{ number_format($ul->min_consumption, 0) }})</small>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            <div style="margin-bottom:15px; text-align:right;">
                                <button type="button" class="btn btn-primary" onclick="createRecord()">
                                    {{ __('members.add_level') }}
                                </button>
                            </div>
                            <table class="table table-hover list-table" id="levels_table">
                                <thead>
                                <tr>
                                    <th>{{ __('common.id') }}</th>
                                    <th>{{ __('members.level_name') }}</th>
                                    <th>{{ __('members.level_code') }}</th>
                                    <th>{{ __('members.discount') }}</th>
                                    <th>{{ __('members.min_consumption') }}</th>
                                    <th>{{ __('members.points_rate') }}</th>
                                    <th>{{ __('members.status') }}</th>
                                    <th>{{ __('common.edit') }}</th>
                                    <th>{{ __('common.delete') }}</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        {{-- Tab 2: 卡号设置 --}}
                        <div class="tab-pane" id="tab_card_number">
                            <form id="settingsForm_card" class="form-horizontal">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <h5 class="text-primary"><i class="fa fa-credit-card"></i> {{ __('members.card_number_setting') }}</h5>
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
                                        <hr>
                                        <div class="form-group">
                                            <div class="col-md-8 col-md-offset-4">
                                                <button type="button" class="btn btn-primary" onclick="saveSettingsTab('settingsForm_card')">
                                                    {{ __('members.settings_save') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Tab 3: 积分规则 --}}
                        <div class="tab-pane" id="tab_points_rules">
                            <form id="settingsForm_points" class="form-horizontal">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <h5 class="text-primary"><i class="fa fa-star"></i> {{ __('members.points_expiry_setting') }}</h5>
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

                                        <div class="form-group">
                                            <label class="control-label col-md-4 text-primary">{{ __('members.points_expiry_days') }}</label>
                                            <div class="col-md-8">
                                                <input type="number" name="points_expiry_days" id="points_expiry_days" class="form-control" min="0" value="{{ $settings['points_expiry_days'] ?? 0 }}">
                                                <small class="text-muted">{{ __('members.points_expiry_days_hint') }}</small>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="text-primary"><i class="fa fa-exchange"></i> {{ __('members.points_exchange_rate') }}</h5>

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

                                        <hr>
                                        <div class="form-group">
                                            <div class="col-md-8 col-md-offset-4">
                                                <button type="button" class="btn btn-primary" onclick="saveSettingsTab('settingsForm_points')">
                                                    {{ __('members.settings_save') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Tab 4: 转介绍 --}}
                        <div class="tab-pane" id="tab_referral">
                            <form id="settingsForm_referral" class="form-horizontal">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <h5 class="text-primary"><i class="fa fa-users"></i> {{ __('members.referral_open_card') }}</h5>
                                        <small class="text-muted">{{ __('members.referral_open_card_hint') }}</small>

                                        <div class="form-group" style="margin-top:15px;">
                                            <label class="control-label col-md-4 text-primary">{{ __('members.referral_bonus_enabled') }}</label>
                                            <div class="col-md-8">
                                                <select name="referral_bonus_enabled" id="referral_bonus_enabled" class="form-control">
                                                    <option value="1" {{ ($settings['referral_bonus_enabled'] ?? false) ? 'selected' : '' }}>{{ __('common.yes') }}</option>
                                                    <option value="0" {{ !($settings['referral_bonus_enabled'] ?? false) ? 'selected' : '' }}>{{ __('common.no') }}</option>
                                                </select>
                                                <small class="text-muted">{{ __('members.referral_bonus_enabled_hint') }}</small>
                                            </div>
                                        </div>

                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i>
                                            {{ __('members.referral_points_hint') }}
                                        </div>

                                        <hr>
                                        <div class="form-group">
                                            <div class="col-md-8 col-md-offset-4">
                                                <button type="button" class="btn btn-primary" onclick="saveSettingsTab('settingsForm_referral')">
                                                    {{ __('members.settings_save') }}
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
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

@include('members.levels.create')
@include('members.levels.edit')
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/DataTableManager.js') }}" type="text/javascript"></script>
<script>
    var dataTable = null;

    LanguageManager.loadAllFromPHP({
        'members': @json(__('members')),
        'messages': @json(__('messages'))
    });

    function createRecord() {
        addLevel();
    }

    function getTableSelector() {
        return '#levels_table';
    }

    function setupEmptyStateHandler() {
        // No-op: integrated into tab layout
    }

    /**
     * Save settings from any tab form
     */
    function saveSettingsTab(formId) {
        var formData = new FormData($('#' + formId)[0]);
        formData.append('_method', 'PUT');

        $.ajax({
            url: '/member-settings',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status) {
                    swal({
                        title: LanguageManager.trans('messages.success'),
                        text: response.message,
                        type: 'success'
                    });
                } else {
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: response.message,
                        type: 'error'
                    });
                }
            },
            error: function(xhr) {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: xhr.responseJSON ? xhr.responseJSON.message : 'Error',
                    type: 'error'
                });
            }
        });
    }

    // Switch to tab via URL hash
    if (window.location.hash) {
        var tab = window.location.hash;
        $('.nav-tabs a[href="' + tab + '"]').tab('show');
    }
</script>
<script src="{{ asset('include_js/member_levels.js') }}"></script>
@endsection
