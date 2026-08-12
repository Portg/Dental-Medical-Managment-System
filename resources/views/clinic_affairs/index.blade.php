@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-page.css') }}?v={{ filemtime(public_path('css/list-page.css')) }}">
<link rel="stylesheet" href="{{ asset('css/form-modal.css') }}?v={{ filemtime(public_path('css/form-modal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/clinic-affairs.css') }}?v={{ filemtime(public_path('css/clinic-affairs.css')) }}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered clinic-affairs-page">
            <div class="portlet-body">
                <div class="clinic-affairs-header">
                    <h1 class="page-title">{{ __('clinic_affairs.' . $activeTab) }}</h1>
                    <div class="clinic-affairs-actions">
                        {{-- href 只是兜底，实际点击由 clinic_affairs.js 带上当前筛选条件 --}}
                        <a class="btn btn-default" id="btn-export-clinic" href="{{ route('clinic-affairs.' . $activeTab . '.export') }}">
                            <i class="fa fa-download"></i> {{ __('clinic_affairs.export') }}
                        </a>
                        @if($canManage)
                        <button type="button" class="btn btn-primary" id="btn-add-clinic-record">
                            <i class="fa fa-plus"></i> {{ __('clinic_affairs.add_record') }}
                        </button>
                        @endif
                    </div>
                </div>

                <div class="row clinic-affairs-stats">
                    <div class="col-sm-3"><div class="clinic-stat"><span>{{ __('clinic_affairs.today_disinfection') }}</span><strong data-clinic-stat="today_disinfection">{{ $stats['today_disinfection'] }}</strong></div></div>
                    <div class="col-sm-3"><div class="clinic-stat clinic-stat-warning"><span>{{ __('clinic_affairs.open_issues') }}</span><strong data-clinic-stat="open_issues">{{ $stats['open_issues'] }}</strong></div></div>
                    <div class="col-sm-3"><div class="clinic-stat"><span>{{ __('clinic_affairs.equipment_due') }}</span><strong data-clinic-stat="equipment_due">{{ $stats['equipment_due'] }}</strong></div></div>
                    <div class="col-sm-3"><div class="clinic-stat"><span>{{ __('clinic_affairs.monthly_waste') }}</span><strong data-clinic-stat="monthly_waste_kg">{{ number_format($stats['monthly_waste_kg'], 2) }} kg</strong></div></div>
                </div>

                <ul class="nav nav-tabs clinic-affairs-tabs">
                    <li><a href="{{ url('sterilization') }}">{{ __('menu.sterilization_management') }}</a></li>
                    <li class="{{ $activeTab === 'disinfection' ? 'active' : '' }}"><a href="{{ route('clinic-affairs.disinfection') }}">{{ __('clinic_affairs.disinfection') }}</a></li>
                    <li class="{{ $activeTab === 'equipment' ? 'active' : '' }}"><a href="{{ route('clinic-affairs.equipment') }}">{{ __('clinic_affairs.equipment') }}</a></li>
                    <li class="{{ $activeTab === 'waste' ? 'active' : '' }}"><a href="{{ route('clinic-affairs.waste') }}">{{ __('clinic_affairs.waste') }}</a></li>
                </ul>

                <div class="clinic-affairs-filter">
                    @if($activeTab === 'disinfection')
                        <select id="filter-primary" class="form-control input-sm">
                            <option value="">{{ __('clinic_affairs.all') }} {{ __('clinic_affairs.check_type') }}</option>
                            @foreach(['clinical_surface', 'housekeeping', 'waterline', 'air_quality', 'other'] as $value)
                            <option value="{{ $value }}">{{ __('clinic_affairs.check_type_' . $value) }}</option>
                            @endforeach
                        </select>
                        <select id="filter-secondary" class="form-control input-sm">
                            <option value="">{{ __('clinic_affairs.all') }} {{ __('clinic_affairs.result') }}</option>
                            <option value="pass">{{ __('clinic_affairs.result_pass') }}</option>
                            <option value="issue">{{ __('clinic_affairs.result_issue') }}</option>
                        </select>
                    @elseif($activeTab === 'equipment')
                        <select id="filter-primary" class="form-control input-sm">
                            <option value="">{{ __('clinic_affairs.all') }} {{ __('clinic_affairs.equipment_category') }}</option>
                            @foreach(['xray', 'sterilizer', 'dental_unit', 'emergency', 'monitoring', 'other'] as $value)
                            <option value="{{ $value }}">{{ __('clinic_affairs.equipment_category_' . $value) }}</option>
                            @endforeach
                        </select>
                        <select id="filter-secondary" class="form-control input-sm">
                            <option value="">{{ __('clinic_affairs.all') }} {{ __('clinic_affairs.due_filter') }}</option>
                            <option value="soon">{{ __('clinic_affairs.due_soon') }}</option>
                            <option value="overdue">{{ __('clinic_affairs.overdue') }}</option>
                        </select>
                    @else
                        <select id="filter-primary" class="form-control input-sm">
                            <option value="">{{ __('clinic_affairs.all') }} {{ __('clinic_affairs.waste_type') }}</option>
                            @foreach(['infectious', 'sharps', 'pharmaceutical', 'chemical', 'other'] as $value)
                            <option value="{{ $value }}">{{ __('clinic_affairs.waste_type_' . $value) }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="button" class="btn btn-default btn-sm" id="btn-reset-clinic-filter">{{ __('common.reset') }}</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-apply-clinic-filter">{{ __('common.search') }}</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover clinic-affairs-table" id="clinic-affairs-table">
                        <thead>
                        @if($activeTab === 'disinfection')
                            <tr><th>#</th><th>{{ __('clinic_affairs.performed_at') }}</th><th>{{ __('clinic_affairs.area') }}</th><th>{{ __('clinic_affairs.check_type') }}</th><th>{{ __('clinic_affairs.result') }}</th><th>{{ __('clinic_affairs.operator') }}</th><th>{{ __('clinic_affairs.review_status') }}</th><th>{{ __('clinic_affairs.actions') }}</th></tr>
                        @elseif($activeTab === 'equipment')
                            <tr><th>#</th><th>{{ __('clinic_affairs.performed_at') }}</th><th>{{ __('clinic_affairs.equipment_code') }}</th><th>{{ __('clinic_affairs.equipment_name') }}</th><th>{{ __('clinic_affairs.equipment_category') }}</th><th>{{ __('clinic_affairs.maintenance_type') }}</th><th>{{ __('clinic_affairs.result') }}</th><th>{{ __('clinic_affairs.next_due_at') }}</th><th>{{ __('clinic_affairs.actions') }}</th></tr>
                        @else
                            <tr><th>#</th><th>{{ __('clinic_affairs.handed_over_at') }}</th><th>{{ __('clinic_affairs.waste_type') }}</th><th>{{ __('clinic_affairs.weight_kg') }}</th><th>{{ __('clinic_affairs.package_count') }}</th><th>{{ __('clinic_affairs.handler') }}</th><th>{{ __('clinic_affairs.receiver_name') }}</th><th>{{ __('clinic_affairs.manifest_no') }}</th><th>{{ __('clinic_affairs.actions') }}</th></tr>
                        @endif
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canManage)
<div class="modal fade" id="clinicRecordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content form-modal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('clinic_affairs.add_record') }}</h4>
            </div>
            <form id="clinic-record-form">
                <div class="modal-body">
                    <input type="hidden" name="id" id="clinic-record-id">
                    @if($activeTab === 'disinfection')
                        <div class="row">
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.area') }} *</label><input name="area" class="form-control" required maxlength="100"></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.check_type') }} *</label><select name="check_type" class="form-control" required>@foreach(['clinical_surface','housekeeping','waterline','air_quality','other'] as $v)<option value="{{ $v }}">{{ __('clinic_affairs.check_type_'.$v) }}</option>@endforeach</select></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.disinfectant') }}</label><input name="disinfectant" class="form-control" maxlength="100"></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.concentration') }}</label><input name="concentration" class="form-control" maxlength="50"></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.performed_at') }} *</label>@include('partials.datetime_picker', ['name' => 'performed_at', 'required' => true])</div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.result') }} *</label><select name="result" class="form-control" required><option value="pass">{{ __('clinic_affairs.result_pass') }}</option><option value="issue">{{ __('clinic_affairs.result_issue') }}</option></select></div>
                            <div class="col-md-12 form-group"><label>{{ __('clinic_affairs.corrective_action') }}</label><textarea name="corrective_action" class="form-control" rows="2" maxlength="1000"></textarea></div>
                        </div>
                    @elseif($activeTab === 'equipment')
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.equipment_code') }} *</label><input name="equipment_code" class="form-control" required maxlength="50"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.equipment_name') }} *</label><input name="equipment_name" class="form-control" required maxlength="100"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.equipment_category') }} *</label><select name="category" class="form-control" required>@foreach(['xray','sterilizer','dental_unit','emergency','monitoring','other'] as $v)<option value="{{ $v }}">{{ __('clinic_affairs.equipment_category_'.$v) }}</option>@endforeach</select></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.location') }}</label><input name="location" class="form-control" maxlength="100"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.maintenance_type') }} *</label><select name="maintenance_type" class="form-control" required>@foreach(['inspection','preventive','repair','calibration'] as $v)<option value="{{ $v }}">{{ __('clinic_affairs.maintenance_type_'.$v) }}</option>@endforeach</select></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.result') }} *</label><select name="result" class="form-control" required>@foreach(['normal','follow_up','out_of_service'] as $v)<option value="{{ $v }}">{{ __('clinic_affairs.equipment_result_'.$v) }}</option>@endforeach</select></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.performed_at') }} *</label>@include('partials.datetime_picker', ['name' => 'performed_at', 'required' => true])</div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.next_due_at') }}</label><input type="text" name="next_due_at" class="form-control js-date" autocomplete="off"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.vendor') }}</label><input name="vendor" class="form-control" maxlength="100"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.cost') }}</label><input type="number" min="0" step="0.01" name="cost" class="form-control"></div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.waste_type') }} *</label><select name="waste_type" class="form-control" required>@foreach(['infectious','sharps','pharmaceutical','chemical','other'] as $v)<option value="{{ $v }}">{{ __('clinic_affairs.waste_type_'.$v) }}</option>@endforeach</select></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.weight_kg') }} *</label><input type="number" min="0.01" step="0.01" name="weight_kg" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.package_count') }} *</label><input type="number" min="1" step="1" name="package_count" value="1" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.handed_over_at') }} *</label>@include('partials.datetime_picker', ['name' => 'handed_over_at', 'required' => true])</div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.receiver_name') }} *</label><input name="receiver_name" class="form-control" required maxlength="100"></div>
                            <div class="col-md-4 form-group"><label>{{ __('clinic_affairs.carrier') }}</label><input name="carrier" class="form-control" maxlength="150"></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.manifest_no') }}</label><input name="manifest_no" class="form-control" maxlength="100"></div>
                            <div class="col-md-6 form-group"><label>{{ __('clinic_affairs.destination') }}</label><input name="destination" class="form-control" maxlength="200"></div>
                        </div>
                    @endif
                    <div class="form-group"><label>{{ __('clinic_affairs.notes') }}</label><textarea name="notes" class="form-control" rows="2" maxlength="1000"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('clinic_affairs.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('clinic_affairs.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('js')
@php
    $clinicAffairsConfig = [
        'tab' => $activeTab,
        'canManage' => $canManage,
        'dataUrl' => route('clinic-affairs.' . $activeTab . '.data'),
        'exportUrl' => route('clinic-affairs.' . $activeTab . '.export'),
        'storeUrl' => $activeTab === 'disinfection'
            ? url('clinic-affairs/disinfection')
            : ($activeTab === 'equipment' ? url('clinic-affairs/equipment-maintenance') : url('clinic-affairs/medical-waste')),
        'deleteBase' => url('clinic-affairs'),
        'reviewBase' => url('clinic-affairs/disinfection'),
    ];
@endphp
<script>
LanguageManager.loadFromPHP(@json(__('clinic_affairs')), 'clinic_affairs');
window.clinicAffairsConfig = @json($clinicAffairsConfig);
</script>
<script src="{{ asset('include_js/clinic_affairs.js') }}?v={{ filemtime(public_path('include_js/clinic_affairs.js')) }}"></script>
@endsection
