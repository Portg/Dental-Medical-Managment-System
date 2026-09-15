@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('css')
    <link href="{{ asset('css/appointment-drawer.css') }}?v={{ filemtime(public_path('css/appointment-drawer.css')) }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/form-modal.css') }}?v={{ filemtime(public_path('css/form-modal.css')) }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work-kanban.css') }}?v={{ filemtime(public_path('css/today-work-kanban.css')) }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work.css') }}?v={{ filemtime(public_path('css/today-work.css')) }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
    {{-- 布局对齐原型 v2：左侧待办轨（办事入口）+ 主区（六格日报 KPI + 列表）。
         KPI 只读扫一眼；角标在侧轨，不把 KPI 做成可点行动数。 --}}
    <div class="tw-workspace">
        <nav class="tw-rail" id="tw-info-tabs" aria-label="{{ __('today_work.title') }}">
            <div class="tw-rail-title">{{ __('today_work.title') }}</div>
            <a href="#tab-today-work" class="tw-rail-item active" data-toggle="tab" data-tab="today-work" role="tab">
                <span>{{ __('today_work.tab_today_visits') }}</span>
                <span class="tw-rail-n" id="badge-today-work">{{ $kpi['today_visits'] ?? $kpi['today_patients'] ?? 0 }}</span>
            </a>
            <a href="#tab-billing" class="tw-rail-item" data-toggle="tab" data-tab="billing" role="tab">
                <span>{{ __('today_work.tab_billing') }}</span>
            </a>
            <a href="#tab-unpaid" class="tw-rail-item tw-rail-warn" data-toggle="tab" data-tab="unpaid" role="tab">
                <span>{{ __('today_work.tab_unpaid_short') }}</span>
                <span class="tw-rail-n" id="badge-unpaid"></span>
            </a>
            <a href="#tab-paid" class="tw-rail-item" data-toggle="tab" data-tab="paid" role="tab">
                <span>{{ __('today_work.tab_paid_short') }}</span>
                <span class="tw-rail-n" id="badge-paid"></span>
            </a>
            <a href="#tab-followups" class="tw-rail-item" data-toggle="tab" data-tab="followups" role="tab">
                <span>{{ __('today_work.tab_followups') }}</span>
                <span class="tw-rail-n" id="badge-followups"></span>
            </a>
            <a href="#tab-tomorrow" class="tw-rail-item" data-toggle="tab" data-tab="tomorrow" role="tab">
                <span>{{ __('today_work.tab_tomorrow') }}</span>
                <span class="tw-rail-n" id="badge-tomorrow"></span>
            </a>
            <a href="#tab-lab-cases" class="tw-rail-item" data-toggle="tab" data-tab="lab-cases" role="tab">
                <span>{{ __('today_work.tab_lab_cases_short') }}</span>
                <span class="tw-rail-n" id="badge-lab-cases"></span>
            </a>
            <a href="#tab-birthdays" class="tw-rail-item" data-toggle="tab" data-tab="birthdays" role="tab">
                <span>{{ __('today_work.tab_birthdays') }}</span>
                <span class="tw-rail-n" id="badge-birthdays"></span>
            </a>
            <a href="#tab-week-missed" class="tw-rail-item" data-toggle="tab" data-tab="week-missed" role="tab">
                <span>{{ __('today_work.tab_week_missed') }}</span>
                <span class="tw-rail-n" id="badge-week-missed"></span>
            </a>
            <a href="#tab-doctor-table" class="tw-rail-item" data-toggle="tab" data-tab="doctor-table" role="tab">
                <span>{{ __('today_work.tab_doctor_table') }}</span>
            </a>
        </nav>

        <div class="tw-workspace-main">
    {{-- 页头动作只留「本页特色」：新预约、叫号大屏。 --}}
    <div class="tw-header">
        <div class="tw-header-actions">
            @can('create-appointments')
            <button type="button" class="btn btn-sm btn-primary" onclick="openAppointmentDrawer()">
                <i class="fa fa-calendar-plus-o"></i> {{ __('today_work.new_appointment') }}
            </button>
            @endcan
            <a class="btn btn-sm btn-default" href="{{ url('waiting-queue/display') }}" target="_blank">
                <i class="fa fa-desktop"></i> {{ __('today_work.display_screen') }}
            </a>
        </div>
    </div>

    {{-- 六格日报 KPI：新增患者 / 新增预约 / 实收 / 欠费 / 回访 / 今日就诊 --}}
    <div class="tw-kpi-row" id="tw-kpi-row">
        <div class="tw-kpi-card">
            <div class="kpi-value" id="kpi-new-patients">{{ $kpi['new_patients'] ?? 0 }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_new_patients') }}</div>
        </div>
        <div class="tw-kpi-card">
            <div class="kpi-value" id="kpi-new-appointments">{{ $kpi['new_appointments'] ?? 0 }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_new_appointments') }}</div>
        </div>
        <div class="tw-kpi-card">
            <div class="kpi-value money" id="kpi-collected">&yen;{{ number_format($kpi['today_collected'] ?? 0, 2) }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_collected') }}</div>
        </div>
        <div class="tw-kpi-card">
            <div class="kpi-value money" id="kpi-outstanding">&yen;{{ number_format($kpi['outstanding_amount'] ?? 0, 2) }}</div>
            <div class="kpi-sub" id="kpi-outstanding-patients">{{ __('today_work.kpi_outstanding_people', ['count' => $kpi['outstanding_patients'] ?? 0]) }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_outstanding') }}</div>
        </div>
        <div class="tw-kpi-card">
            <div class="kpi-value" id="kpi-followups">{{ $kpi['today_followups'] ?? 0 }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_followups') }}</div>
        </div>
        <div class="tw-kpi-card">
            <div class="kpi-value" id="kpi-visits">{{ $kpi['today_visits'] ?? 0 }}</div>
            <div class="kpi-sub" id="kpi-first-visits">{{ __('today_work.kpi_first_visits', ['count' => $kpi['first_visits'] ?? 0]) }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_visits') }}</div>
        </div>
    </div>

    <div class="tab-content tw-tab-content">
        {{-- Tab: Today Work (main content) --}}
        <div role="tabpanel" class="tab-pane active" id="tab-today-work">

    {{-- 状态分档对齐视频：全部 | 未到 | 已到（细状态由看板列承担） --}}
    <ul class="nav nav-pills tw-status-pills" id="tw-status-pills">
        <li class="active"><a href="javascript:;" data-status="all">
            {{ __('today_work.filter_all_statuses') }} <span class="badge" id="pill-all">0</span></a></li>
        <li><a href="javascript:;" data-status="not_arrived">
            {{ __('today_work.not_arrived') }} <span class="badge" id="pill-not_arrived">0</span></a></li>
        <li><a href="javascript:;" data-status="arrived">
            {{ __('today_work.arrived') }} <span class="badge" id="pill-arrived">0</span></a></li>
    </ul>
    {{-- 细状态计数仍由 refreshStats 写入隐藏节点，供侧栏/调试；不占首屏 --}}
    <span id="pill-waiting" class="hidden" hidden>0</span>
    <span id="pill-called" class="hidden" hidden>0</span>
    <span id="pill-in_treatment" class="hidden" hidden>0</span>
    <span id="pill-completed" class="hidden" hidden>0</span>
    <span id="pill-no_show" class="hidden" hidden>0</span>

    {{-- Toolbar --}}
    <div class="tw-toolbar">
        <div class="tw-toolbar-left">
            {{-- 日期带前后箭头：翻前一天/后一天是每天都在做的动作，
                 只给日历要点三下（开面板、翻月、选日）。参考视频的 < 2025-10-28 > --}}
            <div class="tw-date-nav">
                <button type="button" class="btn btn-sm btn-default" onclick="shiftTodayWorkDate(-1)"
                        title="{{ __('today_work.prev_day') }}"><i class="fa fa-chevron-left"></i></button>
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="tw-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTodayWorkFilterChanged()" autocomplete="off">
                <button type="button" class="btn btn-sm btn-default" onclick="shiftTodayWorkDate(1)"
                        title="{{ __('today_work.next_day') }}"><i class="fa fa-chevron-right"></i></button>
                <button type="button" class="btn btn-sm btn-default" onclick="shiftTodayWorkDate(0)"
                        title="{{ __('today_work.back_to_today') }}">{{ __('today_work.today') }}</button>
            </div>
            <select class="form-control input-sm tw-doctor-filter" id="tw-doctor-filter" onchange="onTodayWorkFilterChanged()">
                <option value="">{{ __('today_work.filter_all_doctors') }}</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc['id'] }}">{{ $doc['name'] }}</option>
                @endforeach
            </select>
            <div class="view-toggle">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-default active" id="btn-table-view" onclick="switchView('table')" title="{{ __('today_work.table_view') }}">
                        <i class="fa fa-list"></i>
                    </button>
                    <button class="btn btn-default" id="btn-kanban-view" onclick="switchView('kanban')" title="{{ __('today_work.kanban_view') }}">
                        <i class="fa fa-th-large"></i>
                    </button>
                </div>
                <button class="btn btn-default btn-sm" id="kanban-collapse-btn" onclick="toggleKanbanCollapse()" style="display:none;" title="{{ __('today_work.toggle_collapse') }}">
                    <i class="fa fa-compress"></i>
                </button>
            </div>
            <select class="form-control input-sm tw-status-filter" id="tw-status-filter" onchange="onTodayWorkFilterChanged()" style="display:none;">
                <option value="all">{{ __('today_work.filter_all_statuses') }}</option>
                <option value="not_arrived">{{ __('today_work.not_arrived') }}</option>
                <option value="arrived">{{ __('today_work.arrived') }}</option>
                <option value="waiting">{{ __('today_work.waiting') }}</option>
                <option value="called">{{ __('today_work.called') }}</option>
                <option value="in_treatment">{{ __('today_work.in_treatment') }}</option>
                <option value="completed">{{ __('today_work.completed') }}</option>
                <option value="no_show">{{ __('today_work.no_show') }}</option>
            </select>
        </div>
        <div class="search-box">
            <input type="text" class="form-control input-sm" id="tw-search"
                   placeholder="{{ __('patient.search_patients') }}"
                   autocomplete="off">
            <i class="fa fa-search"></i>
        </div>
    </div>

    {{-- DataTable View --}}
    <div id="tw-table-view">
                <table class="table table-hover" id="tw-table" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.time') }}</th>
                            <th>{{ __('common.patient') }}</th>
                            <th>{{ __('common.phone') }}</th>
                            <th>{{ __('common.doctor') }}</th>
                            <th>{{ __('common.service') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('today_work.col_visit_type') }}</th>
                            <th>{{ __('today_work.col_notes') }}</th>
                            {{-- 「下一步」单列下拉；病历/收费旁挂；更多仅处方/约下次等 --}}
                            <th class="tw-col-act">{{ __('today_work.col_flow') }}</th>
                            <th class="tw-col-act">{{ __('today_work.medical_case') }}</th>
                            <th class="tw-col-act">{{ __('today_work.invoice') }}</th>
                            <th class="tw-col-act">{{ __('today_work.col_more') }}</th>
                        </tr>
                    </thead>
                </table>
    </div>

    {{-- Kanban View --}}
    <div id="tw-kanban-view" style="display:none;">
        <div class="kanban-row">
            <div class="kanban-col" id="kanban-col-not_arrived" data-status="not_arrived">
                <div class="kanban-col-header">{{ __('today_work.not_arrived') }} <span class="badge badge-warning">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
            <div class="kanban-col" id="kanban-col-waiting" data-status="waiting">
                <div class="kanban-col-header">{{ __('today_work.waiting') }} <span class="badge badge-info">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
            <div class="kanban-col" id="kanban-col-called" data-status="called">
                <div class="kanban-col-header">{{ __('today_work.called') }} <span class="badge badge-primary">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
            <div class="kanban-col" id="kanban-col-in_treatment" data-status="in_treatment">
                <div class="kanban-col-header">{{ __('today_work.in_treatment') }} <span class="badge badge-success">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
            <div class="kanban-col" id="kanban-col-completed" data-status="completed">
                <div class="kanban-col-header">{{ __('today_work.completed') }} <span class="badge badge-default">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
            <div class="kanban-col" id="kanban-col-no_show" data-status="no_show">
                <div class="kanban-col-header">{{ __('today_work.no_show') }} <span class="badge badge-danger">0</span></div>
                <div class="kanban-col-body"></div>
            </div>
        </div>
    </div>

        </div>

        {{-- Tab: Billing --}}
        <div role="tabpanel" class="tab-pane" id="tab-billing">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="billing-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('billing')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="billing-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="billing-content" style="display:none;"></div>
        </div>

        {{-- Tab: Paid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-paid">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="paid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('paid')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="paid-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="paid-content" style="display:none;"></div>
        </div>

        {{-- Tab: Unpaid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-unpaid">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="unpaid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('unpaid')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="unpaid-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="unpaid-content" style="display:none;"></div>
        </div>

        {{-- Tab: Follow-ups --}}
        <div role="tabpanel" class="tab-pane" id="tab-followups">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="followups-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('followups')" autocomplete="off">
                <input type="text" class="form-control input-sm" id="followups-search"
                       placeholder="{{ __('today_work.search_patient_hint') }}"
                       onkeyup="debounceTabSearch('followups')" style="width:180px;">
                <select class="form-control input-sm tw-doctor-filter" id="followups-doctor-filter" onchange="onTabFilterChanged('followups')">
                    <option value="">{{ __('today_work.filter_all_doctors') }}</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc['id'] }}">{{ $doc['name'] }}</option>
                    @endforeach
                </select>
                <select class="form-control input-sm" id="followups-type-filter" onchange="onTabFilterChanged('followups')" style="width:100px;">
                    <option value="">{{ __('today_work.filter_all_types') }}</option>
                    <option value="Phone">{{ __('today_work.followup_type_phone') }}</option>
                    <option value="SMS">{{ __('today_work.followup_type_sms') }}</option>
                    <option value="Email">{{ __('today_work.followup_type_email') }}</option>
                    <option value="Visit">{{ __('today_work.followup_type_visit') }}</option>
                    <option value="Other">{{ __('today_work.followup_type_other') }}</option>
                </select>
                <select class="form-control input-sm tw-status-filter" id="followups-status-filter" onchange="onTabFilterChanged('followups')">
                    <option value="">{{ __('today_work.filter_all_statuses') }}</option>
                    <option value="Pending">{{ __('today_work.followup_pending') }}</option>
                    <option value="Completed">{{ __('today_work.followup_completed') }}</option>
                    <option value="Cancelled">{{ __('today_work.followup_cancelled') }}</option>
                    <option value="No Response">{{ __('today_work.followup_no_response') }}</option>
                </select>
            </div>
            <div class="tw-tab-loading" id="followups-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="followups-content" style="display:none;"></div>
        </div>

        {{-- Tab: Tomorrow --}}
        <div role="tabpanel" class="tab-pane" id="tab-tomorrow">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="tomorrow-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('tomorrow')" autocomplete="off">
                <input type="text" class="form-control input-sm" id="tomorrow-search"
                       placeholder="{{ __('today_work.search_patient_hint') }}"
                       onkeyup="debounceTabSearch('tomorrow')" style="width:200px;">
                <select class="form-control input-sm tw-doctor-filter" id="tomorrow-doctor-filter" onchange="onTabFilterChanged('tomorrow')">
                    <option value="">{{ __('today_work.filter_all_doctors') }}</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc['id'] }}">{{ $doc['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tw-tab-loading" id="tomorrow-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="tomorrow-content" style="display:none;"></div>
        </div>

        {{-- Tab: Week Missed --}}
        <div role="tabpanel" class="tab-pane" id="tab-week-missed">
            <div class="tw-tab-toolbar">
                <label style="margin:0; font-weight:normal; font-size:12px; color:#666;">{{ __('today_work.filter_start_date') }}</label>
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="week-missed-start-date"
                       value="{{ date('Y-m-d', strtotime('-7 days')) }}" onchange="onTabFilterChanged('week-missed')" autocomplete="off">
                <label style="margin:0; font-weight:normal; font-size:12px; color:#666;">{{ __('today_work.filter_end_date') }}</label>
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="week-missed-end-date"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('week-missed')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="week-missed-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="week-missed-content" style="display:none;"></div>
        </div>

        {{-- Tab: Lab Cases --}}
        <div role="tabpanel" class="tab-pane" id="tab-lab-cases">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="lab-cases-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('lab-cases')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="lab-cases-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="lab-cases-content" style="display:none;"></div>
        </div>

        {{-- Tab: Birthdays --}}
        <div role="tabpanel" class="tab-pane" id="tab-birthdays">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="birthdays-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('birthdays')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="birthdays-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="birthdays-content" style="display:none;"></div>
        </div>

        {{-- Tab: Doctor Table --}}
        <div role="tabpanel" class="tab-pane" id="tab-doctor-table">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="doctor-table-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('doctor-table')" autocomplete="off">
                <select class="form-control input-sm tw-doctor-filter" id="doctor-table-doctor-filter" onchange="onTabFilterChanged('doctor-table')">
                    <option value="">{{ __('today_work.filter_all_doctors') }}</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc['id'] }}">{{ $doc['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tw-tab-loading" id="doctor-table-loading"><i class="fa fa-spinner fa-spin"></i> {{ __('common.loading') }}</div>
            <div id="doctor-table-content" style="display:none;"></div>
        </div>
    </div>{{-- /.tw-tab-content --}}
        </div>{{-- /.tw-workspace-main --}}
    </div>{{-- /.tw-workspace --}}

    {{-- Patient Detail Drawer (page-level, used by all tabs) --}}
    <div class="patient-drawer-overlay" id="patient-drawer-overlay"></div>
    <div class="patient-drawer" id="patient-drawer">
        <div class="pd-header">
            <div class="pd-header-top">
                <div>
                    <div class="pd-name" id="pd-name"></div>
                    <div class="pd-meta" id="pd-meta"></div>
                    <div class="pd-phone" id="pd-phone"></div>
                </div>
                <button class="pd-close" onclick="closePatientDrawer()">&times;</button>
            </div>
            <div class="pd-allergy" id="pd-allergy"></div>
        </div>
        <div class="pd-body">
            <div id="patient-drawer-loading" style="display:none; text-align:center; padding:40px;">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
            </div>
            <div id="patient-drawer-content" style="display:none;">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active"><a href="#pd-tab-visits" data-toggle="tab">{{ __('today_work.drawer_visits') }}</a></li>
                    <li role="presentation"><a href="#pd-tab-billing" data-toggle="tab">{{ __('today_work.drawer_billing') }}</a></li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="pd-tab-visits"></div>
                    <div role="tabpanel" class="tab-pane" id="pd-tab-billing"></div>
                </div>
            </div>
        </div>
        <div class="pd-footer">
            <a id="pd-detail-link" href="#" class="btn btn-sm btn-primary">
                <i class="fa fa-external-link"></i> {{ __('today_work.view_full_detail') }}
            </a>
        </div>
    </div>

    {{-- Embedded Modals / Drawers --}}
    @can('create-appointments')
        @include('waiting_queue.partials.register_modal')
    @endcan
    @include('patients.create')
    @include('appointments.create')
    @include('medical_cases.create')
    @include('medical_treatment.prescriptions.create')
    {{-- 开单弹窗（appointments.invoices.create）不再引进来：它的 JS 已经随
         92f20f8「两套开单 UI 合成一套」删掉了，在这一页是弹得出来但什么都点不动的
         空壳，还自带 #btnSave / #doctor_id 两个与本页重复的 id。
         「收费」进患者页划价 Tab（带 appointment_id），见 today_work_actions.js 的 quickInvoice。 --}}
@endsection

@section('js')
    <script>
        var csrfToken = '{{ csrf_token() }}';
        LanguageManager.loadAllFromPHP({
            'today_work': @json(__('today_work')),
            'common': @json(__('common')),
            'patient': @json(__('patient')),
            'patient_tags': @json(__('patient_tags'))
        });

        {{-- 页面脚本已拆到 include_js/today_work_index.js，这里只留 Blade 才能算出的值。
             twTabUrls 保留原全局名：today_work_tabs.js 按这个名字读，改名会断。 --}}
        window.twTabUrls = {
            'billing':      '{{ url("today-work/billing") }}',
            'paid':         '{{ url("today-work/paid") }}',
            'unpaid':       '{{ url("today-work/unpaid") }}',
            'followups':    '{{ url("today-work/followups") }}',
            'tomorrow':     '{{ url("today-work/tomorrow") }}',
            'lab-cases':    '{{ url("today-work/lab-cases") }}',
            'week-missed':  '{{ url("today-work/week-missed") }}',
            'birthdays':    '{{ url("today-work/birthdays") }}',
            'doctor-table': '{{ url("today-work/doctor-table") }}',
            'tab-counts':   '{{ url("today-work/tab-counts") }}'
        };

        window.TodayWorkIndexConfig = {
            locale:      '{{ app()->getLocale() }}',
            dataUrl:     '{{ url("today-work/data") }}',
            statsUrl:    '{{ url("today-work/stats") }}',
            utilsScript: '{{ asset("backend/assets/global/scripts/utils.js") }}'
        };
    </script>
    <script src="{{ asset('include_js/registration_modal.js') }}?v={{ filemtime(public_path('include_js/registration_modal.js')) }}"></script>
    <script src="{{ asset('include_js/appointment_drawer.js') }}?v={{ filemtime(public_path('include_js/appointment_drawer.js')) }}"></script>
    <script src="{{ asset('include_js/today_work_actions.js') }}?v={{ filemtime(public_path('include_js/today_work_actions.js')) }}"></script>
    <script src="{{ asset('include_js/today_work_kanban.js') }}?v={{ filemtime(public_path('include_js/today_work_kanban.js')) }}"></script>
    <script src="{{ asset('include_js/today_work_patient_drawer.js') }}?v={{ filemtime(public_path('include_js/today_work_patient_drawer.js')) }}"></script>
    <script src="{{ asset('include_js/today_work_tabs.js') }}?v={{ filemtime(public_path('include_js/today_work_tabs.js')) }}"></script>
    <script src="{{ asset('include_js/today_work_index.js') }}?v={{ filemtime(public_path('include_js/today_work_index.js')) }}"></script>
@endsection
