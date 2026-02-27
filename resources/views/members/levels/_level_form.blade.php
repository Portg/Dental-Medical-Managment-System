{{--
    Shared level form partial.
    Variables:
      $prefix  – '' for create, 'edit_' for edit
      $mode    – 'create' or 'edit'
--}}
@php
    $modalId    = $mode === 'edit' ? 'editLevelModal' : 'levelModal';
    $modalLabel = $mode === 'edit' ? 'editLevelModalLabel' : 'levelModalLabel';
    $formId     = $mode === 'edit' ? 'editLevelForm' : 'levelForm';
    $titleKey   = $mode === 'edit' ? 'members.edit_level' : 'members.add_level';
    $saveAction = $mode === 'edit' ? 'updateLevel()' : 'saveLevel()';
    $bonusTbody = $prefix . 'bonus_rules_body';
    $pmTbody    = $prefix . 'pm_points_body';
@endphp

<div class="modal fade modal-form modal-form-lg" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalLabel }}" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="{{ $modalLabel }}">{{ __($titleKey) }}</h4>
            </div>
            <div class="modal-body">
                <form id="{{ $formId }}" class="form-horizontal">
                    @csrf
                    @if($mode === 'edit')
                        <input type="hidden" name="level_id" id="{{ $prefix }}level_id">
                    @endif
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    {{-- Section 1: 基本信息 --}}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.level_name') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <input type="text" name="name" id="{{ $prefix }}level_name" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.level_code') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <input type="text" name="code" id="{{ $prefix }}level_code" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.discount_rate') }} <span class="text-danger">*</span></label>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="number" name="discount_rate" id="{{ $prefix }}level_discount_rate" class="form-control" step="0.01" min="0" max="100" value="100" required>
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <small class="text-muted">{{ __('members.discount_rate_hint') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.color') }}</label>
                                <div class="col-md-8">
                                    <input type="color" name="color" id="{{ $prefix }}level_color" class="form-control" value="#999999">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.sort_order') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="sort_order" id="{{ $prefix }}level_sort_order" class="form-control" min="0" value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.is_active') }}</label>
                                <div class="col-md-8">
                                    <select name="is_active" id="{{ $prefix }}level_is_active" class="form-control">
                                        <option value="1">{{ __('common.yes') }}</option>
                                        <option value="0">{{ __('common.no') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: 开卡与升级 --}}
                    <hr>
                    <h5 class="text-primary"><i class="fa fa-credit-card"></i> {{ __('members.section_card_upgrade') }}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.opening_fee') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="opening_fee" id="{{ $prefix }}level_opening_fee" class="form-control" step="0.01" min="0" value="0">
                                    <small class="text-muted">{{ __('members.opening_fee_hint') }}</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.min_initial_deposit') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="min_initial_deposit" id="{{ $prefix }}level_min_initial_deposit" class="form-control" step="0.01" min="0" value="0">
                                    <small class="text-muted">{{ __('members.min_initial_deposit_hint') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.min_consumption') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="min_consumption" id="{{ $prefix }}level_min_consumption" class="form-control" step="0.01" min="0" value="0">
                                    <small class="text-muted">{{ __('members.min_consumption_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: 充值赠送规则 --}}
                    <hr>
                    <h5 class="text-primary"><i class="fa fa-gift"></i> {{ __('members.deposit_bonus_rules') }}</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <small class="text-muted">{{ __('members.deposit_bonus_rules_hint') }}</small>
                            <table class="table table-condensed" style="margin-top:8px;" id="{{ $prefix }}bonus_rules_table">
                                <thead>
                                    <tr>
                                        <th style="width:40%">{{ __('members.bonus_min_amount') }}</th>
                                        <th style="width:40%">{{ __('members.bonus_amount') }}</th>
                                        <th style="width:20%"></th>
                                    </tr>
                                </thead>
                                <tbody id="{{ $bonusTbody }}">
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-default" onclick="addBonusRule('{{ $bonusTbody }}')">
                                {{ __('members.add_bonus_rule') }}
                            </button>
                        </div>
                    </div>

                    {{-- Section 4: 积分设置 --}}
                    <hr>
                    <h5 class="text-primary"><i class="fa fa-star"></i> {{ __('members.section_points') }}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.points_rate') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="points_rate" id="{{ $prefix }}level_points_rate" class="form-control" step="0.01" min="0" value="1">
                                    <small class="text-muted">{{ __('members.points_rate_hint') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label col-md-4 text-primary">{{ __('members.referral_points') }}</label>
                                <div class="col-md-8">
                                    <input type="number" name="referral_points" id="{{ $prefix }}level_referral_points" class="form-control" step="0.01" min="0" value="0">
                                    <small class="text-muted">{{ __('members.referral_points_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 5: 收款方式积分比例 --}}
                    <hr>
                    <h5 class="text-primary"><i class="fa fa-money"></i> {{ __('members.payment_method_points_rates') }}</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <small class="text-muted">{{ __('members.payment_method_points_rates_hint') }}</small>
                            <table class="table table-condensed" style="margin-top:8px;" id="{{ $prefix }}pm_points_table">
                                <thead>
                                    <tr>
                                        <th style="width:40%">{{ __('members.payment_method') }}</th>
                                        <th style="width:40%">{{ __('members.points_rate') }}</th>
                                        <th style="width:20%"></th>
                                    </tr>
                                </thead>
                                <tbody id="{{ $pmTbody }}">
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-default" onclick="addPointsRateRow('{{ $pmTbody }}')">
                                {{ __('members.add_bonus_rule') }}
                            </button>
                        </div>
                    </div>

                    {{-- Section 6: 权益说明 --}}
                    <hr>
                    <div class="form-group">
                        <label class="control-label col-md-2 text-primary">{{ __('members.benefits') }}</label>
                        <div class="col-md-10">
                            <textarea name="benefits" id="{{ $prefix }}level_benefits" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="{{ $saveAction }}">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
