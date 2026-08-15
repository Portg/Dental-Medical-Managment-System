@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('css')
    <link href="{{ asset('css/appointment-drawer.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/form-modal.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work-kanban.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
    {{-- Page Header — actions only, title expressed through tabs --}}
    <div class="tw-header">
        <div></div>
        <div class="tw-header-actions">
            <button class="btn btn-sm btn-success" onclick="quickRegisterPatient()">
                <i class="fa fa-plus"></i> {{ __('today_work.new_patient') }}
            </button>
            <button class="btn btn-sm btn-primary" onclick="openAppointmentDrawer()">
                {{ __('today_work.new_appointment') }}
            </button>
            <a class="btn btn-sm btn-default" href="{{ url('waiting-queue/display') }}" target="_blank">
                {{ __('today_work.display_screen') }}
            </a>
        </div>
    </div>

    {{-- KPI Row — flat, dense, no redundant "今日" prefix --}}
    <div class="tw-kpi-row" id="tw-kpi-row">
        <div class="tw-kpi-card patients">
            <div class="kpi-value" id="kpi-patients">{{ $kpi['today_patients'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_patients') }}</div>
        </div>
        <div class="tw-kpi-card doctors">
            <div class="kpi-value" id="kpi-doctors">{{ $kpi['today_doctors'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_doctors') }}</div>
        </div>
        <div class="tw-kpi-card revisits">
            <div class="kpi-value" id="kpi-revisits">{{ $kpi['today_revisits'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_revisits') }}</div>
        </div>
        <div class="tw-kpi-card appointments">
            <div class="kpi-value" id="kpi-appointments">{{ $kpi['today_appointments'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_appointments') }}</div>
        </div>
        <div class="tw-kpi-card receivable">
            <div class="kpi-value money" id="kpi-receivable">&yen;{{ $kpi['today_receivable'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_receivable') }}</div>
        </div>
        <div class="tw-kpi-card collected">
            <div class="kpi-value money" id="kpi-collected">&yen;{{ $kpi['today_collected'] }}</div>
            <div class="kpi-label">{{ __('today_work.kpi_collected') }}</div>
        </div>
    </div>

    {{-- Information Tabs --}}
    <ul class="nav nav-tabs tw-info-tabs" id="tw-info-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#tab-today-work" data-toggle="tab" data-tab="today-work">{{ __('today_work.tab_today_work') }}</a></li>
        <li role="presentation"><a href="#tab-billing" data-toggle="tab" data-tab="billing">{{ __('today_work.tab_billing') }}</a></li>
        <li role="presentation"><a href="#tab-paid" data-toggle="tab" data-tab="paid">{{ __('today_work.tab_paid') }} <span class="badge tw-tab-badge" id="badge-paid"></span></a></li>
        <li role="presentation"><a href="#tab-unpaid" data-toggle="tab" data-tab="unpaid">{{ __('today_work.tab_unpaid') }} <span class="badge tw-tab-badge" id="badge-unpaid"></span></a></li>
        <li role="presentation"><a href="#tab-followups" data-toggle="tab" data-tab="followups">{{ __('today_work.tab_followups') }} <span class="badge tw-tab-badge" id="badge-followups"></span></a></li>
        <li role="presentation"><a href="#tab-tomorrow" data-toggle="tab" data-tab="tomorrow">{{ __('today_work.tab_tomorrow') }} <span class="badge tw-tab-badge" id="badge-tomorrow"></span></a></li>
        <li role="presentation"><a href="#tab-lab-cases" data-toggle="tab" data-tab="lab-cases">{{ __('today_work.tab_lab_cases') }} <span class="badge tw-tab-badge" id="badge-lab-cases"></span></a></li>
        <li role="presentation"><a href="#tab-birthdays" data-toggle="tab" data-tab="birthdays">{{ __('today_work.tab_birthdays') }} <span class="badge tw-tab-badge" id="badge-birthdays"></span></a></li>
        <li role="presentation"><a href="#tab-week-missed" data-toggle="tab" data-tab="week-missed">{{ __('today_work.tab_week_missed') }} <span class="badge tw-tab-badge" id="badge-week-missed"></span></a></li>
        <li role="presentation"><a href="#tab-doctor-table" data-toggle="tab" data-tab="doctor-table">{{ __('today_work.tab_doctor_table') }}</a></li>
    </ul>

    <div class="tab-content tw-tab-content">
        {{-- Tab: Today Work (main content) --}}
        <div role="tabpanel" class="tab-pane active" id="tab-today-work">

    {{-- Toolbar --}}
    <div class="tw-toolbar">
        <div class="tw-toolbar-left">
            <input type="text" class="form-control input-sm tw-date-picker js-date" id="tw-date-filter"
                   value="{{ date('Y-m-d') }}" onchange="onTodayWorkFilterChanged()" autocomplete="off">
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
                <option value="waiting">{{ __('today_work.waiting') }}</option>
                <option value="called">{{ __('today_work.called') }}</option>
                <option value="in_treatment">{{ __('today_work.in_treatment') }}</option>
                <option value="completed">{{ __('today_work.completed') }}</option>
                <option value="no_show">{{ __('today_work.no_show') }}</option>
            </select>
        </div>
        <div class="search-box">
            <input type="text" class="form-control input-sm" id="tw-search"
                   placeholder="{{ __('today_work.search_patient') }}"
                   onkeyup="debounceSearch()">
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
                            <th>{{ __('common.action') }}</th>
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
            <div class="tw-tab-loading" id="billing-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="billing-content" style="display:none;"></div>
        </div>

        {{-- Tab: Paid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-paid">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="paid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('paid')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="paid-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="paid-content" style="display:none;"></div>
        </div>

        {{-- Tab: Unpaid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-unpaid">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="unpaid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('unpaid')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="unpaid-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
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
            <div class="tw-tab-loading" id="followups-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
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
            <div class="tw-tab-loading" id="tomorrow-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
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
            <div class="tw-tab-loading" id="week-missed-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="week-missed-content" style="display:none;"></div>
        </div>

        {{-- Tab: Lab Cases --}}
        <div role="tabpanel" class="tab-pane" id="tab-lab-cases">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="lab-cases-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('lab-cases')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="lab-cases-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="lab-cases-content" style="display:none;"></div>
        </div>

        {{-- Tab: Birthdays --}}
        <div role="tabpanel" class="tab-pane" id="tab-birthdays">
            <div class="tw-tab-toolbar">
                <input type="text" class="form-control input-sm tw-date-picker js-date" id="birthdays-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('birthdays')" autocomplete="off">
            </div>
            <div class="tw-tab-loading" id="birthdays-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
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
            <div class="tw-tab-loading" id="doctor-table-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="doctor-table-content" style="display:none;"></div>
        </div>
    </div>

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
    @include('patients.create')
    @include('appointments.create')
    @include('medical_cases.create')
    @include('medical_treatment.prescriptions.create')
    @include('appointments.invoices.create')
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
            'doctor-table': '{{ url("today-work/doctor-table") }}'
        };

        window.TodayWorkIndexConfig = {
            locale:      '{{ app()->getLocale() }}',
            dataUrl:     '{{ url("today-work/data") }}',
            statsUrl:    '{{ url("today-work/stats") }}',
            utilsScript: '{{ asset("backend/assets/global/scripts/utils.js") }}'
        };
    </script>
    <script src="{{ asset('include_js/appointment_drawer.js') }}"></script>
    <script src="{{ asset('include_js/today_work_actions.js') }}"></script>
    <script src="{{ asset('include_js/today_work_kanban.js') }}"></script>
    <script src="{{ asset('include_js/today_work_patient_drawer.js') }}"></script>
    <script src="{{ asset('include_js/today_work_tabs.js') }}"></script>
    <script src="{{ asset('include_js/today_work_index.js') }}?v={{ filemtime(public_path('include_js/today_work_index.js')) }}"></script>
@endsection
