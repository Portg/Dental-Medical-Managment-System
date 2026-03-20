@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/member-tabs.css') }}">
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('members.page_title') }} / {{ __('members.member_details') }}</span>
                </div>
                <div class="actions">
                    <a href="{{ url('members') }}" class="btn btn-default">
                        {{ __('common.back') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="row">
                    <!-- Member Info Card -->
                    <div class="col-md-4">
                        @php
                            $levelColor = $patient->memberLevel->color ?? '#999999';
                            $levelName  = $patient->memberLevel->name ?? '-';
                            $discount   = $patient->memberLevel ? $patient->memberLevel->discount_rate : 100;
                            $discountDisplay = $discount < 100 ? number_format($discount / 10, 1) . __('members.discount_unit') : '-';
                            $statusClass = 'default';
                            if($patient->member_status == 'Active') $statusClass = 'success';
                            elseif($patient->member_status == 'Expired') $statusClass = 'danger';
                        @endphp
                        {{-- Visual Member Card --}}
                        <div style="background:{{ $levelColor }}; color:#fff; border-radius:8px; padding:20px; margin-bottom:15px; position:relative; overflow:hidden;">
                            <div style="position:absolute; top:10px; right:15px; font-size:28px; font-weight:bold; opacity:0.3;">
                                {{ $discountDisplay }}
                            </div>
                            <div style="font-size:16px; font-weight:bold; margin-bottom:4px;">{{ $levelName }}</div>
                            <div style="font-size:12px; opacity:0.8; margin-bottom:12px;">{{ __('members.balance') }}</div>
                            <div style="font-size:28px; font-weight:bold; margin-bottom:8px;">&yen; {{ number_format($patient->member_balance, 2) }}</div>
                            <div style="font-size:12px; opacity:0.8;">No.{{ $patient->member_no }}</div>
                        </div>

                        {{-- Detail Info --}}
                        <div class="panel panel-default">
                            <div class="panel-body" style="padding:12px 15px;">
                                <table class="table table-condensed" style="margin-bottom:0;">
                                    <tr>
                                        <td style="border-top:none;"><strong>{{ __('members.patient_name') }}:</strong></td>
                                        <td style="border-top:none;">{{ $patient->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.status') }}:</strong></td>
                                        <td><span class="label label-{{ $statusClass }}">{{ __('members.status_' . strtolower($patient->member_status)) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.points') }}:</strong></td>
                                        <td>{{ number_format($patient->member_points) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.total_consumption') }}:</strong></td>
                                        <td>{{ number_format($patient->total_consumption, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.member_since') }}:</strong></td>
                                        <td>{{ $patient->member_since ? \Carbon\Carbon::parse($patient->member_since)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('members.expiry_date') }}:</strong></td>
                                        <td>{{ $patient->member_expiry ? \Carbon\Carbon::parse($patient->member_expiry)->format('Y-m-d') : '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="panel panel-default">
                            <div class="panel-body" style="padding:10px 15px;">
                                <button class="btn btn-success btn-block" onclick="depositMember({{ $patient->id }})">
                                    <i class="fa fa-plus"></i> {{ __('members.deposit') }}
                                </button>
                                <button class="btn btn-danger btn-block" onclick="refundMember({{ $patient->id }})">
                                    <i class="fa fa-minus"></i> {{ __('members.refund') }}
                                </button>
                                @if(\App\SystemSetting::get('member.points_exchange_enabled', true) && \App\SystemSetting::get('member.points_enabled', true))
                                <button class="btn btn-warning btn-block" onclick="showExchangePoints({{ $patient->id }}, {{ $patient->member_points ?? 0 }})">
                                    <i class="fa fa-exchange"></i> {{ __('members.exchange_points') }}
                                </button>
                                @endif
                                <button class="btn btn-primary btn-block" onclick="editMember({{ $patient->id }})">
                                    <i class="fa fa-edit"></i> {{ __('common.edit') }}
                                </button>
                                <button class="btn btn-default btn-block" onclick="showSetPassword({{ $patient->id }})">
                                    <i class="fa fa-lock"></i> {{ __('members.set_password') }}
                                </button>
                                <a class="btn btn-default btn-block" href="{{ url('members/' . $patient->id . '/print') }}" target="_blank">
                                    <i class="fa fa-print"></i> {{ __('members.print_card') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Tabbed Content -->
                    <div class="col-md-8">
                        <div class="tabbable-line">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="#tab_transactions" data-toggle="tab">{{ __('members.transaction_history') }}</a>
                                </li>
                                <li>
                                    <a href="#tab_shared_holders" data-toggle="tab">{{ __('members.shared_holders') }}</a>
                                </li>
                                <li>
                                    <a href="#tab_audit_log" data-toggle="tab">{{ __('members.audit_log') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                {{-- Tab: 交易记录 --}}
                                <div class="tab-pane active" id="tab_transactions">
                                    <table class="table table-striped table-bordered table-hover" id="transactions_table">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('members.transaction_no') }}</th>
                                            <th>{{ __('members.transaction_type') }}</th>
                                            <th>{{ __('members.amount') }}</th>
                                            <th>{{ __('members.balance_after') }}</th>
                                            <th>{{ __('members.payment_method') }}</th>
                                            <th>{{ __('members.date') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                {{-- Tab: 共同持卡人 --}}
                                <div class="tab-pane" id="tab_shared_holders">
                                    <div style="margin-bottom:10px; text-align:right;">
                                        <button class="btn btn-xs btn-primary" onclick="showAddSharedHolder()">
                                            <i class="fa fa-plus"></i> {{ __('members.add_shared_holder') }}
                                        </button>
                                    </div>
                                    <table class="table table-striped table-bordered table-hover" id="shared_holders_table">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('members.shared_patient') }}</th>
                                            <th>{{ __('members.relationship') }}</th>
                                            <th>{{ __('common.action') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                {{-- Tab: 变更记录 --}}
                                <div class="tab-pane" id="tab_audit_log">
                                    <table class="table table-striped table-bordered table-hover" id="audit_logs_table">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('members.audit_action') }}</th>
                                            <th>{{ __('members.audit_field') }}</th>
                                            <th>{{ __('members.audit_old_value') }}</th>
                                            <th>{{ __('members.audit_new_value') }}</th>
                                            <th>{{ __('members.audit_operator') }}</th>
                                            <th>{{ __('members.audit_time') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
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

@include('members.edit')
@include('members.deposit')
@include('members.refund')

@endsection
@section('js')
    <script>
    window.MemberShowConfig = {
        memberId:      {{ $patient->id }},
        levels:        @json($levels),
        memberSettings:@json(\App\SystemSetting::getGroup('member'))
    };
    // Compatibility shims for members.js globals
    var memberId      = window.MemberShowConfig.memberId;
    var levels        = window.MemberShowConfig.levels;
    var memberSettings= window.MemberShowConfig.memberSettings;
    LanguageManager.loadAllFromPHP({
        'members':  @json(__('members')),
        'messages': @json(__('messages'))
    });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/members.js') }}"></script>
    <script src="{{ asset('include_js/member_show.js') }}?v={{ filemtime(public_path('include_js/member_show.js')) }}"></script>
@endsection
