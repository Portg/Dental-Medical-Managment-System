@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('css')
    <link href="{{ asset('css/appointment-drawer.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/form-modal.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work-kanban.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/today-work.css') }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
    {{-- Page Header --}}
    <div class="tw-header">
        <h3>{{ __('today_work.title') }}</h3>
        <div class="tw-header-actions">
            <button class="btn btn-sm btn-success" onclick="quickRegisterPatient()">
                {{ __('today_work.new_patient') }}
            </button>
            <button class="btn btn-sm btn-primary" onclick="openAppointmentDrawer()">
                {{ __('today_work.new_appointment') }}
            </button>
            <a class="btn btn-sm btn-default" href="{{ url('waiting-queue/display') }}" target="_blank">
                {{ __('today_work.display_screen') }}
            </a>
        </div>
    </div>

    {{-- KPI Row (6 cards) --}}
    <div class="tw-kpi-row" id="tw-kpi-row">
        <div class="tw-kpi-card patients">
            <div class="kpi-value" id="kpi-patients">{{ $kpi['today_patients'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_patients') }}</div>
        </div>
        <div class="tw-kpi-card doctors">
            <div class="kpi-value" id="kpi-doctors">{{ $kpi['today_doctors'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_doctors') }}</div>
        </div>
        <div class="tw-kpi-card revisits">
            <div class="kpi-value" id="kpi-revisits">{{ $kpi['today_revisits'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_revisits') }}</div>
        </div>
        <div class="tw-kpi-card appointments">
            <div class="kpi-value" id="kpi-appointments">{{ $kpi['today_appointments'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_appointments') }}</div>
        </div>
        <div class="tw-kpi-card receivable">
            <div class="kpi-value money" id="kpi-receivable">&yen;{{ $kpi['today_receivable'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_receivable') }}</div>
        </div>
        <div class="tw-kpi-card collected">
            <div class="kpi-value money" id="kpi-collected">&yen;{{ $kpi['today_collected'] }}</div>
            <div class="kpi-label">{{ __('today_work.today_collected') }}</div>
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
            <input type="date" class="form-control input-sm tw-date-picker" id="tw-date-filter"
                   value="{{ date('Y-m-d') }}" onchange="onTodayWorkFilterChanged()">
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
        <div class="portlet light bordered">
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover" id="tw-table" width="100%">
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
        </div>
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
                <input type="date" class="form-control input-sm tw-date-picker" id="billing-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('billing')">
            </div>
            <div class="tw-tab-loading" id="billing-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="billing-content" style="display:none;"></div>
        </div>

        {{-- Tab: Paid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-paid">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="paid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('paid')">
            </div>
            <div class="tw-tab-loading" id="paid-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="paid-content" style="display:none;"></div>
        </div>

        {{-- Tab: Unpaid Today --}}
        <div role="tabpanel" class="tab-pane" id="tab-unpaid">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="unpaid-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('unpaid')">
            </div>
            <div class="tw-tab-loading" id="unpaid-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="unpaid-content" style="display:none;"></div>
        </div>

        {{-- Tab: Follow-ups --}}
        <div role="tabpanel" class="tab-pane" id="tab-followups">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="followups-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('followups')">
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
                <input type="date" class="form-control input-sm tw-date-picker" id="tomorrow-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('tomorrow')">
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
                <input type="date" class="form-control input-sm tw-date-picker" id="week-missed-start-date"
                       value="{{ date('Y-m-d', strtotime('-7 days')) }}" onchange="onTabFilterChanged('week-missed')">
                <label style="margin:0; font-weight:normal; font-size:12px; color:#666;">{{ __('today_work.filter_end_date') }}</label>
                <input type="date" class="form-control input-sm tw-date-picker" id="week-missed-end-date"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('week-missed')">
            </div>
            <div class="tw-tab-loading" id="week-missed-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="week-missed-content" style="display:none;"></div>
        </div>

        {{-- Tab: Lab Cases --}}
        <div role="tabpanel" class="tab-pane" id="tab-lab-cases">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="lab-cases-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('lab-cases')">
            </div>
            <div class="tw-tab-loading" id="lab-cases-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="lab-cases-content" style="display:none;"></div>
        </div>

        {{-- Tab: Birthdays --}}
        <div role="tabpanel" class="tab-pane" id="tab-birthdays">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="birthdays-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('birthdays')">
            </div>
            <div class="tw-tab-loading" id="birthdays-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            <div id="birthdays-content" style="display:none;"></div>
        </div>

        {{-- Tab: Doctor Table --}}
        <div role="tabpanel" class="tab-pane" id="tab-doctor-table">
            <div class="tw-tab-toolbar">
                <input type="date" class="form-control input-sm tw-date-picker" id="doctor-table-date-filter"
                       value="{{ date('Y-m-d') }}" onchange="onTabFilterChanged('doctor-table')">
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
            'patient': @json(__('patient'))
        });
    </script>
    <script src="{{ asset('include_js/appointment_drawer.js') }}"></script>
    <script src="{{ asset('include_js/today_work_actions.js') }}"></script>
    <script src="{{ asset('include_js/today_work_kanban.js') }}"></script>
    <script src="{{ asset('include_js/today_work_patient_drawer.js') }}"></script>
    <script src="{{ asset('include_js/today_work_tabs.js') }}"></script>
    <script>
        var twSearchTimer = null;
        var twTable;
        var twCurrentView = localStorage.getItem('tw_view_mode') || 'table';

        // Tab AJAX URLs (passed to today_work_tabs.js)
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

        $(document).ready(function() {
            twTable = $('#tw-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ url("today-work/data") }}',
                    data: function(d) {
                        d.status = $('#tw-status-filter').val();
                        d.search_patient = $('#tw-search').val();
                        d.date = $('#tw-date-filter').val();
                        d.doctor_id = $('#tw-doctor-filter').val();
                    }
                },
                columns: [
                    { data: 'queue_number', name: 'wq.queue_number', orderable: false,
                      render: function(data) { return data || '-'; } },
                    { data: 'start_time', name: 'a.sort_by' },
                    { data: 'patient_name', name: 'p.surname', orderable: false },
                    { data: 'patient_phone', orderable: false, searchable: false },
                    { data: 'doctor_name', name: 'd.surname', orderable: false },
                    { data: 'service', name: 'ms.name', orderable: false },
                    { data: 'display_status', orderable: false, searchable: false },
                    { data: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'asc']],
                pageLength: 50,
                language: LanguageManager.getDataTableLang(),
                dom: 'rtip'
            });

            // Apply saved view preference
            if (twCurrentView === 'kanban') {
                switchView('kanban', true);
            }

            // Initialize info tabs (from today_work_tabs.js)
            initInfoTabs();

            // Load tab count badges
            loadTabCounts();

            // Auto-refresh every 30 seconds
            setInterval(function() {
                refreshStats();
                refreshCurrentView();
            }, 30000);

            // Update treatment durations every 60 seconds
            setInterval(function() {
                if (typeof updateKanbanDurations === 'function') {
                    updateKanbanDurations();
                }
            }, 60000);
        });

        // ── View Switching ─────────────────────────────────
        function switchView(mode, skipSave) {
            twCurrentView = mode;
            if (!skipSave) {
                localStorage.setItem('tw_view_mode', mode);
            }
            if (mode === 'kanban') {
                $('#tw-table-view').hide();
                $('#tw-kanban-view').show();
                $('#btn-table-view').removeClass('active');
                $('#btn-kanban-view').addClass('active');
                $('#kanban-collapse-btn').show();
                $('#tw-status-filter').hide();
                loadKanbanData();
            } else {
                $('#tw-kanban-view').hide();
                $('#tw-table-view').show();
                $('#btn-kanban-view').removeClass('active');
                $('#btn-table-view').addClass('active');
                $('#kanban-collapse-btn').hide();
                $('#tw-status-filter').show();
                twTable.ajax.reload(null, false);
            }
        }

        function refreshCurrentView() {
            if (twCurrentView === 'kanban') {
                loadKanbanData();
            } else {
                twTable.ajax.reload(null, false);
            }
        }

        // Called when today-work tab's own filters change (date/doctor/status)
        function onTodayWorkFilterChanged() {
            if (twCurrentView === 'table') {
                twTable.ajax.reload();
            } else {
                loadKanbanData();
            }
            refreshStats();
            loadTabCounts();
        }

        // Called when any other tab's filter changes
        function onTabFilterChanged(tab) {
            loadTabData(tab);
        }

        function debounceSearch() {
            clearTimeout(twSearchTimer);
            twSearchTimer = setTimeout(function() {
                if (twCurrentView === 'table') {
                    twTable.ajax.reload();
                }
            }, 400);
        }

        var tabSearchTimers = {};
        function debounceTabSearch(tab) {
            clearTimeout(tabSearchTimers[tab]);
            tabSearchTimers[tab] = setTimeout(function() {
                loadTabData(tab);
            }, 400);
        }

        function refreshStats() {
            var params = {
                date: $('#tw-date-filter').val(),
                doctor_id: $('#tw-doctor-filter').val()
            };
            $.getJSON('{{ url("today-work/stats") }}', params, function(data) {
                $('#kpi-patients').text(data.kpi.today_patients);
                $('#kpi-doctors').text(data.kpi.today_doctors);
                $('#kpi-revisits').text(data.kpi.today_revisits);
                $('#kpi-appointments').text(data.kpi.today_appointments);
                $('#kpi-receivable').html('&yen;' + data.kpi.today_receivable);
                $('#kpi-collected').html('&yen;' + data.kpi.today_collected);
            });
        }

        function refreshAppointments() {
            refreshCurrentView();
            refreshStats();
        }

        // ==================================================================
        // Patient Form Initialization (patients.create modal)
        // ==================================================================

        // intl-tel-input
        var _twPhoneInput = document.querySelector("#telephone");
        var _twIti = null;
        if (_twPhoneInput && window.intlTelInput) {
            window.intlTelInput(_twPhoneInput, {
                onlyCountries: ["cn"],
                initialCountry: "cn",
                autoPlaceholder: "off",
                utilsScript: "{{ asset('backend/assets/global/scripts/utils.js') }}",
            });
            _twIti = window.intlTelInputGlobals.getInstance(_twPhoneInput);

            _twPhoneInput.addEventListener('blur', function() { validatePhone(); });
            _twPhoneInput.addEventListener('input', function() {
                var vd = document.getElementById('phone-validation');
                if (vd) vd.style.display = 'none';
                _twPhoneInput.classList.remove('is-invalid', 'is-valid');
            });
        }

        function validatePhone() {
            var validationDiv = document.getElementById('phone-validation');
            var phoneValue = _twPhoneInput ? _twPhoneInput.value.trim() : '';

            if (!phoneValue) {
                if (_twPhoneInput) _twPhoneInput.classList.add('is-invalid');
                if (validationDiv) {
                    validationDiv.textContent = '{{ __("validation.required", ["attribute" => __("patient.phone_no")]) }}';
                    validationDiv.className = 'validation-message error';
                    validationDiv.style.display = 'block';
                }
                return false;
            }

            var cleanNumber = phoneValue.replace(/\D/g, '');
            if (cleanNumber.startsWith('86')) cleanNumber = cleanNumber.substring(2);

            if (!/^1[3-9]\d{9}$/.test(cleanNumber)) {
                if (_twPhoneInput) _twPhoneInput.classList.add('is-invalid');
                if (validationDiv) {
                    validationDiv.textContent = '{{ __("patient.invalid_phone") }}';
                    validationDiv.className = 'validation-message error';
                    validationDiv.style.display = 'block';
                }
                return false;
            }

            if (_twPhoneInput) {
                _twPhoneInput.classList.add('is-valid');
                _twPhoneInput.classList.remove('is-invalid');
            }
            if (validationDiv) validationDiv.style.display = 'none';
            return true;
        }

        // Select2: patient source
        $.get('/patient-sources-list', function(data) {
            $('#source_id').select2({
                language: '{{ app()->getLocale() }}',
                placeholder: "{{ __('patient_tags.select_source') }}",
                allowClear: true,
                data: data
            });
        });

        // Select2: patient tags
        $.get('/patient-tags-list', function(data) {
            $('#patient_tags').select2({
                language: '{{ app()->getLocale() }}',
                placeholder: "{{ __('patient_tags.select_tags') }}",
                allowClear: true,
                multiple: true,
                data: data
            });
        });

        // Select2: insurance company
        $('#company').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('patient.choose_insurance_company') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-insurance-company',
                dataType: 'json',
                delay: 300,
                data: function(params) { return { q: $.trim(params.term) }; },
                processResults: function(data) { return { results: data }; },
                cache: true
            }
        });

        // Insurance toggle
        $('.insurance_company').hide();
        $("input[type=radio][name=has_insurance]").on("change", function() {
            var action = $("input[type=radio][name=has_insurance]:checked").val();
            if (action == "0") {
                $('#company').val([]).trigger('change');
                $('.insurance_company').hide();
                $('#company').next(".select2-container").hide();
            } else {
                $('.insurance_company').show();
                $('#company').next(".select2-container").show();
            }
        });

        // Patient form save
        function save_data(continueAdding) {
            if (!validatePhone()) {
                if (_twPhoneInput) _twPhoneInput.focus();
                return;
            }
            if (_twIti) {
                $('#phone_number').val(_twIti.getNumber());
            }
            var id = $('#id').val();
            if (id === "" || !id) {
                save_new_record(continueAdding);
            } else {
                update_record();
            }
        }

        function save_new_record(continueAdding) {
            $.LoadingOverlay("show");
            $('#btnSave, #btnSaveAndContinue').attr('disabled', true);
            $.ajax({
                type: 'POST',
                data: $('#patient-form').serialize(),
                url: "/patients",
                success: function(data) {
                    $.LoadingOverlay("hide");
                    $('#btnSave, #btnSaveAndContinue').attr('disabled', false);
                    if (data.status) {
                        toastr.success(data.message);
                        if (continueAdding) {
                            var currentSource = $('#source_id').val();
                            $("#patient-form")[0].reset();
                            $('#id').val('');
                            $('#patient_tags').val(null).trigger('change');
                            $('#company').val([]).trigger('change');
                            $('.insurance_company').hide();
                            if (_twIti) { _twIti.setNumber(''); }
                            $('#phone_number').val('');
                            if (currentSource) {
                                $('#source_id').val(currentSource).trigger('change');
                            }
                            if (typeof resetPatientFormToCreateMode === 'function') {
                                resetPatientFormToCreateMode();
                            }
                            if (typeof clearHealthInfo === 'function') {
                                clearHealthInfo();
                            }
                        } else {
                            $('#patients-modal').modal('hide');
                        }
                        refreshStats();
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function(request) {
                    $.LoadingOverlay("hide");
                    $('#btnSave, #btnSaveAndContinue').attr('disabled', false);
                    if (request.responseJSON && request.responseJSON.errors) {
                        $.each(request.responseJSON.errors, function(key, value) {
                            toastr.error(value[0] || value);
                        });
                    } else {
                        toastr.error('{{ __("common.error_message") }}');
                    }
                }
            });
        }

        function update_record() {
            $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $.ajax({
                type: 'PUT',
                data: $('#patient-form').serialize(),
                url: "/patients/" + $('#id').val(),
                success: function(data) {
                    $.LoadingOverlay("hide");
                    $('#btnSave').attr('disabled', false);
                    if (data.status) {
                        toastr.success(data.message);
                        $('#patients-modal').modal('hide');
                        refreshStats();
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function(request) {
                    $.LoadingOverlay("hide");
                    $('#btnSave').attr('disabled', false);
                    if (request.responseJSON && request.responseJSON.errors) {
                        $.each(request.responseJSON.errors, function(key, value) {
                            toastr.error(value[0] || value);
                        });
                    } else {
                        toastr.error('{{ __("common.error_message") }}');
                    }
                }
            });
        }
    </script>
@endsection
