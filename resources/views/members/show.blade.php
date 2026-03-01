@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
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
        var memberId = {{ $patient->id }};
        var levels = @json($levels);
        var memberSettings = @json(\App\SystemSetting::getGroup('member'));

        LanguageManager.loadAllFromPHP({
            'members': @json(__('members')),
            'messages': @json(__('messages'))
        });
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/members.js') }}"></script>
    <script>
        $(document).ready(function() {
            loadTransactions();
            loadSharedHolders();
            loadAuditLogs();
        });

        function loadSharedHolders() {
            $('#shared_holders_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/members/' + memberId + '/shared-holders',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'patient_name', name: 'patient_name'},
                    {data: 'relationship', name: 'relationship'},
                    {data: 'removeBtn', name: 'removeBtn', orderable: false, searchable: false}
                ],
                language: LanguageManager.getDataTableLang(),
                paging: false,
                info: false
            });
        }

        function loadAuditLogs() {
            $('#audit_logs_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/members/' + memberId + '/audit-logs',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'actionBadge', name: 'actionBadge'},
                    {data: 'field_name', name: 'field_name'},
                    {data: 'old_value', name: 'old_value'},
                    {data: 'new_value', name: 'new_value'},
                    {data: 'operator_name', name: 'operator_name'},
                    {data: 'created_at', name: 'created_at'}
                ],
                order: [[6, 'desc']],
                language: LanguageManager.getDataTableLang()
            });
        }

        function showAddSharedHolder() {
            swal({
                title: LanguageManager.trans('members.add_shared_holder'),
                text: LanguageManager.trans('members.shared_patient'),
                type: 'input',
                showCancelButton: true,
                confirmButtonText: LanguageManager.trans('common.save'),
                cancelButtonText: LanguageManager.trans('common.cancel'),
                inputPlaceholder: LanguageManager.trans('members.shared_patient') + ' ID',
                closeOnConfirm: false
            }, function(inputValue) {
                if (inputValue === false) return;
                var patientId = parseInt(inputValue);
                if (isNaN(patientId) || patientId <= 0) {
                    swal.showInputError(LanguageManager.trans('members.shared_patient'));
                    return false;
                }
                $.ajax({
                    url: '/members/' + memberId + '/shared-holders',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        shared_patient_id: patientId,
                        relationship: 'other'
                    },
                    success: function(response) {
                        if (response.status) {
                            swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                            $('#shared_holders_table').DataTable().ajax.reload();
                        } else {
                            swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                        }
                    }
                });
            });
        }

        function removeSharedHolder(id) {
            swal({
                title: LanguageManager.trans('messages.confirm_delete'),
                text: LanguageManager.trans('members.confirm_remove_shared'),
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: LanguageManager.trans('common.delete'),
                cancelButtonText: LanguageManager.trans('common.cancel')
            }, function(isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        url: '/members/shared-holders/' + id,
                        type: 'DELETE',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function(response) {
                            if (response.status) {
                                swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                                $('#shared_holders_table').DataTable().ajax.reload();
                            } else {
                                swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                            }
                        }
                    });
                }
            });
        }

        function showSetPassword(patientId) {
            swal({
                title: LanguageManager.trans('members.set_password'),
                text: LanguageManager.trans('members.new_password'),
                type: 'input',
                showCancelButton: true,
                confirmButtonText: LanguageManager.trans('common.save'),
                cancelButtonText: LanguageManager.trans('common.cancel'),
                inputType: 'password',
                closeOnConfirm: false
            }, function(inputValue) {
                if (inputValue === false) return;
                if (!inputValue || inputValue.length < 4) {
                    swal.showInputError(LanguageManager.trans('members.password_too_short'));
                    return false;
                }
                $.ajax({
                    url: '/members/' + patientId + '/password',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        password: inputValue,
                        password_confirmation: inputValue
                    },
                    success: function(response) {
                        if (response.status) {
                            swal({ title: LanguageManager.trans('messages.success'), text: response.message, type: 'success' });
                        } else {
                            swal({ title: LanguageManager.trans('messages.error'), text: response.message, type: 'error' });
                        }
                    },
                    error: function(xhr) {
                        swal({ title: LanguageManager.trans('messages.error'), text: xhr.responseJSON ? xhr.responseJSON.message : 'Error', type: 'error' });
                    }
                });
            });
        }
    </script>
@endsection
