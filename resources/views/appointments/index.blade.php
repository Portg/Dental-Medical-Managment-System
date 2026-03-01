@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    {{-- Unified list page styles --}}
    <link rel="stylesheet" href="{{ asset('css/list-page.css') }}">
    {{-- Unified form modal styles --}}
    <link rel="stylesheet" href="{{ asset('css/form-modal.css') }}">
    {{-- Appointment drawer styles --}}
    <link rel="stylesheet" href="{{ asset('css/appointment-drawer.css') }}">
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                <div class="tabbable tabbable-tabdrop">
                    <ul class="nav nav-pills">

                        <li class="active" id="appointments_tab_link">
                            <a href="#appointments_tab" data-toggle="tab" aria-expanded="true">{{ __('appointment.appointments') }}</a>
                        </li>
                        <li class="" id="appointment_calender_tab_link">
                            <a href="#appointment_calender_tab" data-toggle="tab" aria-expanded="false">{{ __('appointment.appointments_calender') }}
                            </a>
                        </li>
                        <li class="" id="doctor_day_view_tab_link">
                            <a href="#doctor_day_view_tab" data-toggle="tab" aria-expanded="false">{{ __('appointment.doctor_day_view') }}</a>
                        </li>

                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="appointments_tab">
                            <div class="row">
                                <div class="portlet light">
                                    <div class="portlet-body">
                                        {{-- L1: Page Header --}}
                                        <div class="page-header-l1">
                                            <h1 class="page-title">{{ __('appointment.appointment_mgt') }}</h1>
                                            <div class="header-actions">
                                                <a href="{{ url('export-appointments') }}" class="btn btn-default">
                                                    <i class="icon-cloud-download"></i> {{ __('common.download_excel_report') }}
                                                </a>
                                                <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('appointment.add_appointment') }}</button>
                                            </div>
                                        </div>

                                        {{-- L2: Filter Area --}}
                                        <div class="filter-area-l2">
                                            <div class="row filter-row">
                                                {{-- Quick Search --}}
                                                <div class="col-md-4">
                                                    <div class="filter-label">{{ __('common.search') }}</div>
                                                    <div class="input-group">
                                                        <span class="input-group-addon"><i class="icon-magnifier"></i></span>
                                                        <input type="text" class="form-control" id="quickSearch"
                                                               placeholder="{{ __('appointment.quick_search_placeholder') }}">
                                                    </div>
                                                </div>
                                                {{-- Period Selector --}}
                                                <div class="col-md-3">
                                                    <div class="filter-label">{{ __('datetime.period') }}</div>
                                                    <select class="form-control" id="period_selector">
                                                        <option value="">{{__('datetime.time_periods.all')}}</option>
                                                        <option value="Today">{{__('datetime.time_periods.today')}}</option>
                                                        <option value="Yesterday">{{__('datetime.time_periods.yesterday')}}</option>
                                                        <option value="This week">{{__('datetime.time_periods.this_week')}}</option>
                                                        <option value="Last week">{{__('datetime.time_periods.last_week')}}</option>
                                                        <option value="This Month">{{__('datetime.time_periods.this_month')}}</option>
                                                        <option value="Last Month">{{__('datetime.time_periods.last_month')}}</option>
                                                    </select>
                                                </div>
                                                {{-- Date Range with connector --}}
                                                <div class="col-md-4">
                                                    <div class="filter-label">{{ __('datetime.date_range.title') }}</div>
                                                    <div class="date-range-wrapper">
                                                        <input type="text" class="form-control start_date" placeholder="{{__('datetime.date_range.start_date')}}" style="flex: 1;">
                                                        <span class="date-separator">{{__('datetime.date_range.to')}}</span>
                                                        <input type="text" class="form-control end_date" placeholder="{{__('datetime.date_range.end_date')}}" style="flex: 1;">
                                                        <button type="button" class="btn btn-default" id="toggleAdvancedFilter" title="{{ __('common.advanced_filter') }}">
                                                            <i class="icon-equalizer"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Advanced Filters (Collapsible) --}}
                                            <div id="advancedFilters" class="advanced-filters-section" style="display: none;">
                                                <div class="row filter-row">
                                                    <div class="col-md-4">
                                                        <div class="filter-label">{{ __('appointment.appointment_no') }}</div>
                                                        <input type="text" class="form-control"
                                                               placeholder="{{ __('appointment.enter_appointment_no') }}"
                                                               name="appointment_no"
                                                               id="appointment_no_filter">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="filter-label">{{ __('appointment.doctor') }}</div>
                                                        <select class="form-control" id="filter_doctor">
                                                            <option value="">{{ __('common.all') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="filter-label">{{ __('appointment.invoice_status') }}</div>
                                                        <select class="form-control" id="filter_invoice_status">
                                                            <option value="">{{ __('common.all') }}</option>
                                                            <option value="pending">{{ __('common.pending') }}</option>
                                                            <option value="invoiced">{{ __('appointment.invoiced') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12" style="margin-top: 10px;">
                                                        <button type="button" class="btn btn-sm btn-default" id="clearFilters">
                                                            {{ __('common.reset') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <table class="table table-hover list-table"
                                               id="appointments-table">
                                            <thead>
                                            <tr>
                                                <th>{{ __('common.id') }}</th>
                                                <th>{{ __('appointment.appointment_date') }}</th>
                                                <th>{{ __('appointment.appointment_time') }}</th>
                                                <th>{{ __('appointment.patient') }}</th>
                                                <th>{{ __('appointment.doctor') }}</th>
                                                <th>{{ __('appointment.appointment_category') }}</th>
                                                <th>{{ __('appointment.invoice_status') }}</th>
                                                <th>{{ __('common.action') }}</th>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>


                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="tab-pane" id="appointment_calender_tab">
                            <div class="row">
                                <div class="portlet light">
                                    <div class="portlet-title">
                                        <div class="caption font-dark">
                                            <span class="caption-subject"> {{__('appointment.appointment_mgt')}}/ {{__('appointment.appointments_calender')}}</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <div id="calendar"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Appointment event popover (hidden template, positioned by JS) --}}
                            <div id="apt-popover" class="apt-popover" style="display:none;">
                                <div class="apt-popover-header">
                                    <span id="apt-popover-patient"></span>
                                    <span id="apt-popover-phone" class="apt-popover-phone"></span>
                                </div>
                                <div class="apt-popover-body">
                                    <div class="apt-popover-row">
                                        <span class="apt-popover-label">{{ __('appointment.popover_time') }}</span>
                                        <span id="apt-popover-time"></span>
                                    </div>
                                    <div class="apt-popover-row">
                                        <span class="apt-popover-label">{{ __('appointment.doctor') }}</span>
                                        <span id="apt-popover-doctor"></span>
                                    </div>
                                    <div class="apt-popover-row">
                                        <span class="apt-popover-label">{{ __('appointment.popover_project') }}</span>
                                        <span id="apt-popover-service"></span>
                                    </div>
                                    <div class="apt-popover-row">
                                        <span class="apt-popover-label">{{ __('appointment.popover_status') }}</span>
                                        <span id="apt-popover-status" class="apt-popover-status-badge"></span>
                                    </div>
                                    <div class="apt-popover-row" id="apt-popover-notes-row" style="display:none;">
                                        <span class="apt-popover-label">{{ __('appointment.notes') }}</span>
                                        <span id="apt-popover-notes"></span>
                                    </div>
                                </div>
                                <div class="apt-popover-actions">
                                    <button type="button" class="btn btn-xs btn-primary" id="apt-popover-edit">
                                        <i class="fa fa-pencil"></i> {{ __('common.edit') }}
                                    </button>
                                    <button type="button" class="btn btn-xs btn-info" id="apt-popover-reschedule">
                                        <i class="fa fa-calendar"></i> {{ __('appointment.reschedule') }}
                                    </button>
                                    <button type="button" class="btn btn-xs btn-warning" id="apt-popover-sms">
                                        <i class="fa fa-envelope"></i> {{ __('appointment.popover_send_sms') }}
                                    </button>
                                    <button type="button" class="btn btn-xs btn-danger" id="apt-popover-delete">
                                        <i class="fa fa-trash"></i> {{ __('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- Doctor Day View Tab --}}
                        <div class="tab-pane" id="doctor_day_view_tab">
                            <div class="row">
                                <div class="portlet light">
                                    <div class="portlet-title">
                                        <div class="caption font-dark">
                                            <span class="caption-subject">{{ __('appointment.doctor_day_view') }}</span>
                                        </div>
                                    </div>
                                    <div class="portlet-body">
                                        <div class="drg-toolbar">
                                            <button type="button" class="btn btn-sm btn-default" id="drg-prev"><i class="fa fa-chevron-left"></i></button>
                                            <button type="button" class="btn btn-sm btn-default" id="drg-today">{{ __('appointment.today') }}</button>
                                            <button type="button" class="btn btn-sm btn-default" id="drg-next"><i class="fa fa-chevron-right"></i></button>
                                            <span class="drg-date-label" id="drg-date-label"></span>
                                        </div>
                                        <div class="drg-container" id="drg-container">
                                            {{-- Rendered by appointment_resource_grid.js --}}
                                        </div>
                                    </div>
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
@include('appointments.create')
@include('appointments.invoices.create')
@include('appointments.reschedule_appointment')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/reschedule_appointment.js') }}" type="text/javascript"></script>
    {{-- Load page-specific translations BEFORE appointment_drawer.js so LanguageManager.trans() works --}}
    <script type="text/javascript">
        LanguageManager.loadAllFromPHP({
            'appointment': @json(__('appointment')),
            'datetime': @json(__('datetime')),
            'patient': @json(__('patient')),
            'messages': @json(__('messages'))
        });
    </script>
    <script src="{{ asset('include_js/appointment_drawer.js') }}?v={{ filemtime(public_path('include_js/appointment_drawer.js')) }}" type="text/javascript"></script>
    <script type="text/javascript">
        // Debounce helper function (design spec: 300ms debounce for auto-search)
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function default_todays_data() {
            // initially load today's date filtered data
            $('.start_date').val(todaysDate());
            $('.end_date').val(todaysDate());
            $("#period_selector").val('Today');
        }

        // Period selector with auto-apply
        $('#period_selector').on('change', function () {
            switch (this.value) {
                case'Today':
                    $('.start_date').val(todaysDate());
                    $('.end_date').val(todaysDate());
                    break;
                case'Yesterday':
                    $('.start_date').val(YesterdaysDate());
                    $('.end_date').val(YesterdaysDate());
                    break;
                case'This week':
                    $('.start_date').val(thisWeek());
                    $('.end_date').val(todaysDate());
                    break;
                case'Last week':
                    lastWeek();
                    break;
                case'This Month':
                    $('.start_date').val(formatDate(thisMonth()));
                    $('.end_date').val(todaysDate());
                    break;
                case'Last Month':
                    lastMonth();
                    break;
                default:
                    // All - clear dates
                    $('.start_date').val('');
                    $('.end_date').val('');
                    break;
            }
            // Auto-apply filter on change
            $('#appointments-table').DataTable().draw(true);
        });

        // Toggle advanced filters
        $('#toggleAdvancedFilter').on('click', function() {
            $('#advancedFilters').slideToggle(200);
            $(this).toggleClass('active');
        });

        // Reset filters to initial defaults (today's date)
        $('#clearFilters').on('click', function() {
            $('#quickSearch').val('');
            $('#appointment_no_filter').val('');
            $('#filter_doctor').val('');
            $('#filter_invoice_status').val('');
            default_todays_data();
            $('#appointments-table').DataTable().draw(true);
        });


        let services_arry = [];
        var table; // Declare table variable in outer scope

        $(function () {
            //hide appointment date time inputs
            $('.appointment_section').hide();

            default_todays_data();  //filter  date
            table = $('#appointments-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),  // 使用当前语言配置
                ajax: {
                    url: "{{ url('/appointments/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.appointment_no = $('#appointment_no_filter').val();
                        d.quick_search = $('#quickSearch').val();
                        d.filter_doctor = $('#filter_doctor').val();
                        d.filter_invoice_status = $('#filter_invoice_status').val();
                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'Brtip', // Removed 'f' (default search box) since we have custom search
                buttons: {
                    buttons: []
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'start_date', name: 'start_date'},
                    {data: 'start_time', name: 'start_time'},
                    {data: 'patient', name: 'patient'},
                    {data: 'doctor', name: 'doctor'},
                    {data: 'visit_information', name: 'visit_information'},
                    {data: 'invoice_status', name: 'invoice_status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });

            // Quick search with 300ms debounce (design spec: INT-001)
            var debouncedSearch = debounce(function() {
                table.draw(true);
            }, 300);
            $('#quickSearch').on('keyup', debouncedSearch);

            // Appointment No filter with debounce
            $('#appointment_no_filter').on('keyup', debouncedSearch);

            // Auto-apply other filters on change
            $('#filter_doctor, #filter_invoice_status').on('change', function() {
                table.draw(true);
            });

            // Date change also triggers filter
            $('.start_date, .end_date').on('change', function() {
                table.draw(true);
            });
        });

        function createRecord() {
            // Use new drawer-based appointment form (design spec F-APT-001)
            if (typeof openAppointmentDrawer === 'function') {
                openAppointmentDrawer({
                    date: todaysDate()
                });
            } else {
                // Fallback to old modal if drawer not available
                $("#appointment-form")[0].reset();
                $('#id').val('');
                $('#appointment-modal').modal('show');
                $('.appointment_date').val(todaysDate());
                $('#appointment_time').val(currentTimeSelect());
                $('#patient').val([]).trigger('change');
                $('#doctor').val([]).trigger('change');
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text("{{ __('common.save_record') }}");
            }
        }


        //filter patients
        $('#patient').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: LanguageManager.trans('appointment.choose_patient'),
            minimumInputLength: 2,
            ajax: {
                url: '/search-patient',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        //filter doctor
        $('#doctor').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: LanguageManager.trans('appointment.choose_doctor'),
            minimumInputLength: 2,
            ajax: {
                url: '/search-doctor',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        //invoice filter doctors

        $('#doctor_id').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: LanguageManager.trans('appointment.procedure_done_by'),
            minimumInputLength: 2,
            ajax: {
                url: '/search-doctor',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });


        function save_data() {
            //check save method
            var id = $('#id').val();
            if (id === "") {
                save_new_record();
            } else {
                update_record();
            }
        }

        function save_new_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text("{{ __('common.processing') }}");
            $.ajax({
                type: 'POST',
                data: $('#appointment-form').serialize(),
                url: "/appointments",
                success: function (data) {
                    $('#appointment-modal').modal('hide');
                    $.LoadingOverlay("hide");
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#btn-save').attr('disabled', false);
                    $('#btn-save').text("{{ __('common.save_record') }}");
                    $('#appointment-modal').modal('show');
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function editRecord(id) {
            // Use new drawer-based appointment form (design spec F-APT-001)
            if (typeof openAppointmentDrawer === 'function') {
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'get',
                    url: "appointments/" + id + "/edit",
                    success: function (data) {
                        $.LoadingOverlay("hide");
                        // Open drawer and populate with edit data
                        openAppointmentDrawer();

                        // Set appointment ID for update
                        $('#appointment_id').val(id);
                        $('#drawer-title').text("{{ __('appointment.edit_appointment') }}");

                        // Populate patient
                        let patient_data = {
                            id: data.patient_id,
                            text: LanguageManager.joinName(data.surname, data.othername)
                        };
                        let patientOption = new Option(patient_data.text, patient_data.id, true, true);
                        $('#drawer_patient').append(patientOption).trigger('change');

                        // Show patient info if available
                        if (typeof showPatientInfoCard === 'function' && data.patient) {
                            showPatientInfoCard(data.patient);
                        }

                        // Populate doctor
                        let doctor_data = {
                            id: data.doctor_id,
                            text: LanguageManager.joinName(data.d_surname, data.d_othername)
                        };
                        let doctorOption = new Option(doctor_data.text, doctor_data.id, true, true);
                        $('#drawer_doctor').append(doctorOption).trigger('change');

                        // Populate date and time
                        $('#appointment_date').val(data.start_date);
                        if (typeof updateWeekday === 'function') {
                            updateWeekday(data.start_date);
                        }
                        $('#appointment_time').val(data.start_time);

                        // Populate other fields
                        $('[name="notes"]').val(data.notes);
                        if (data.appointment_type) {
                            $('input[name="appointment_type"][value="' + data.appointment_type + '"]').prop('checked', true);
                        }
                        if (data.duration_minutes) {
                            $('#duration_minutes').val(data.duration_minutes);
                        }
                        if (data.chair_id) {
                            // Load chair option
                            $.ajax({
                                url: '/api/chairs',
                                success: function(chairs) {
                                    chairs.forEach(function(chair) {
                                        if (chair.id == data.chair_id) {
                                            let chairOption = new Option(chair.text, chair.id, true, true);
                                            $('#drawer_chair').append(chairOption).trigger('change');
                                        }
                                    });
                                }
                            });
                        }
                        if (data.service_id) {
                            // Load service option
                            $.ajax({
                                url: '/search-medical-service?id=' + data.service_id,
                                success: function(services) {
                                    if (services && services.length > 0) {
                                        let serviceOption = new Option(services[0].text, services[0].id, true, true);
                                        $('#drawer_service').append(serviceOption).trigger('change');
                                    }
                                }
                            });
                        }

                        // Load time slots after doctor is set
                        setTimeout(function() {
                            if (typeof loadTimeSlots === 'function') {
                                loadTimeSlots();
                                // Highlight selected time after slots load
                                setTimeout(function() {
                                    var timeStr = data.start_time;
                                    if (timeStr) {
                                        // Convert to HH:mm format
                                        var timeParts = timeStr.match(/(\d{1,2}):(\d{2})/);
                                        if (timeParts) {
                                            var formattedTime = timeParts[1].padStart(2, '0') + ':' + timeParts[2];
                                            $('.time-slot').each(function() {
                                                if ($(this).text().trim().startsWith(formattedTime)) {
                                                    $(this).addClass('selected');
                                                }
                                            });
                                        }
                                    }
                                }, 500);
                            }
                        }, 100);
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        toastr.error("{{ __('messages.error_occurred') }}");
                    }
                });
            } else {
                // Fallback to old modal
                $("#appointment-form")[0].reset();
                $('#id').val('');
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text("{{ __('common.update_record') }}");
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'get',
                    url: "appointments/" + id + "/edit",
                    success: function (data) {
                        $('#id').val(id);
                        let patient_data = { id: data.patient_id, text: LanguageManager.joinName(data.surname, data.othername) };
                        $('#patient').append(new Option(patient_data.text, patient_data.id, true, true)).trigger('change');
                        let doctor_data = { id: data.doctor_id, text: LanguageManager.joinName(data.d_surname, data.d_othername) };
                        $('#doctor').append(new Option(doctor_data.text, doctor_data.id, true, true)).trigger('change');
                        $('input[name^="visit_information"][value="' + data.visit_information + '"').prop('checked', true);
                        $('[name="notes"]').val(data.notes);
                        $('#appointment_status').val(data.status);
                        $('.appointment_date').val(data.start_date);
                        $('#appointment_time').val(data.start_time);
                        $.LoadingOverlay("hide");
                        $('#btn-save').text("{{ __('common.update_record') }}");
                        $('#appointment-modal').modal('show');
                    },
                    error: function () { $.LoadingOverlay("hide"); }
                });
            }
        }

        function update_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text("{{ __('common.updating') }}");
            $.ajax({
                type: 'PUT',
                data: $('#appointment-form').serialize(),
                url: "/appointments/" + $('#id').val(),
                success: function (data) {
                    $('#appointment-modal').modal('hide');
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#btn-save').attr('disabled', false);
                    $('#btn-save').text("{{ __('common.save_record') }}");
                    $('#appointment-modal').modal('show');
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        //reactivate treatment of the patient
        function ReactivateAppointment(id) {
            $("#appointment-form")[0].reset()
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('.modal-title').text("{{ __('appointment.re_activate_appointment') }}");
            $.LoadingOverlay("show");
            $.ajax({
                type: 'get',
                url: "appointments/" + id + "/edit",
                success: function (data) {
                    $('#id').val(id);
                    let patient_data = {
                        id: data.patient_id,
                        text: LanguageManager.joinName(data.surname, data.othername)
                    };
                    let newOption = new Option(patient_data.text, patient_data.id, true, true);
                    $('#patient').append(newOption).trigger('change');

                    let doctor_data = {
                        id: data.doctor_id,
                        text: LanguageManager.joinName(data.d_surname, data.d_othername)
                    };
                    let newOption2 = new Option(doctor_data.text, doctor_data.id, true, true);
                    $('#doctor').append(newOption2).trigger('change');
                    $('input[name^="visit_information"][value="' + data.visit_information + '"').prop('checked', true);
                    $('[name="notes"]').val(data.notes);
                    $('#appointment_status').val(data.status);

                    // $('#visit_info_section').hide();
                    $('#reactivated_appointment').val("yes")
                    $.LoadingOverlay("hide");
                    $('#btn-save').text("{{ __('appointment.reactivate_appointment') }}")
                    $('#appointment-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }


        function deleteRecord(id) {
            var sweetAlertLang = LanguageManager.getSweetAlertLang();
            swal({
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('appointment.delete_appointment_warning')}}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('common.yes_delete_it') }}",
                    closeOnConfirm: false
                },
                function () {

                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'delete',
                        data: {
                            _token: CSRF_TOKEN
                        },
                        url: "appointments/" + id,
                        success: function (data) {
                            if (data.status) {
                                alert_dialog(data.message, "success");
                                if (window._appointmentCalendar) window._appointmentCalendar.refetchEvents();
                                if (window._drgInstance) window._drgInstance._load();
                                if ($('#appointments-table').length) $('#appointments-table').DataTable().draw(false);
                            } else {
                                alert_dialog(data.message, "danger");
                            }
                            $.LoadingOverlay("hide");
                        },
                        error: function (request, status, error) {
                            $.LoadingOverlay("hide");

                        }
                    });

                });

        }

        // system admin and receptionists Invoice generation section

        function RecordPayment(id) {
            $("#New-invoice-form")[0].reset();
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text("{{ __('appointment.generate_invoice') }}");

            $('#invoicing_appointment_id').val(id);
            $('#New-invoice-modal').modal('show');
        }

        $(document).on('click', '.remove-tr', function () {

            $(this).parents('tr').remove();

        });
        //filter Procedures
        $('#service').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: LanguageManager.trans('appointment.select_procedure'),
            minimumInputLength: 2,
            ajax: {
                url: '/search-medical-service',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        }).on("select2:select", function (e) {
            let price = e.params.data.price;
            if (price != "" || price != 0) {
                $('#procedure_price').val(price);
                $('#procedure_qty').val(1);
                let amount = ($('#procedure_price').val().replace(/,/g, "")) * $('#procedure_qty').val();
                $('#total_amount').val(structureMoney("" + amount));
            } else {
                $('#procedure_price').val('');
                $('#procedure_qty').val('');
            }

        });


        //get all the services in an array
        $(document).ready(function () {

            $('#procedure_qty').on('keyup change', function () {
                if ($(this).val() && $('#procedure_price').val()) {
                    $('#total_amount').val(structureMoney("" + $(this).val() * ($('#procedure_price').val().replace(/,/g, ""))))
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }

            });

            $('#procedure_price').on('keyup change', function () {
                if ($(this).val() && $('#procedure_qty').val()) {
                    $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#procedure_qty').val()))
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }
            });


            //show appointment date and time section

            $("input[type=radio][name=visit_information]").on("change", function () {
                let action = $("input[type=radio][name=visit_information]:checked").val();

                if (action == "walk_in") {
                    //hide appointment date time inputs
                    $('.appointment_section').hide();
                } else {
                    $('.appointment_date').val(todaysDate());
                    $('#appointment_time').val(currentTimeSelect());

                    //show appointment date time inputs
                    $('.appointment_section').show();
                }

            });


        });


        let i = 0;
        $("#addInvoiceItem").click(function () {
            ++i;

            $("#InvoicesTable").append('<tr>' +
                '<td><select id="service_append' + i + '" name="addmore[' + i + '][medical_service_id]" class="form-control"\n' +
                '                                        style="width: 100%;border: 1px solid #a29e9e;"></select></td>' +
                '<td> <input type="text" name="addmore[' + i + '][tooth_no]" placeholder="' + "{{ __('appointment.enter_tooth_number') }}" + '"\n' +
                '                                       class="form-control"/></td>' +
                '<td> <input type="number" onkeyup="QTYKeyChange(' + i + ')" id="procedure_qty' + i + '" name="addmore[' + i + '][qty]" placeholder="' + "{{ __('appointment.enter_qty') }}" + '"\n' +
                '                                       class="form-control"/></td>' +
                '<td> <input type="number" onkeyup="PriceKeyChange(' + i + ')"  id="procedure_price' + i + '" name="addmore[' + i + '][price]" placeholder="' + "{{ __('appointment.enter_unit_price') }}" + '"\n' +
                '                                       class="form-control"/></td>' +
                '<td> <input type="text"  id="total_amount' + i + '"  class="form-control" readonly/></td>' +
                '<td><select id="doctor_id_append' + i + '" name="addmore[' + i + '][doctor_id]" class="form-control"\n' +
                '                                        style="width: 100%;border: 1px solid #a29e9e;"></select></td>' +
                '<td><button type="button" class="btn btn-danger remove-tr">' + "{{ __('common.delete') }}" + '</button></td></tr>');

            //append procedure doctor
            $('#doctor_id_append' + i).select2({
                language: '{{ app()->getLocale() }}',
                placeholder: LanguageManager.trans('appointment.procedure_done_by'),
                minimumInputLength: 2,
                ajax: {
                    url: '/search-doctor',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: $.trim(params.term)
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });


            $('#service_append' + i).select2({
                language: '{{ app()->getLocale() }}',
                placeholder: LanguageManager.trans('appointment.select_procedure'),
                minimumInputLength: 2,
                ajax: {
                    url: '/search-medical-service',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: $.trim(params.term)
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on("select2:select", function (e) {
                let price = e.params.data.price;
                if (price != "" || price != 0) {
                    $('#procedure_price' + i).val(price);
                    $('#procedure_qty' + i).val(1);
                    let amount = ($('#procedure_price' + i).val().replace(/,/g, "")) * $('#procedure_qty' + i).val();
                    $('#total_amount' + i).val(structureMoney("" + amount));
                } else {
                    $('#procedure_price' + i).val('');
                    $('#procedure_qty' + i).val('')
                }

            });


        });


        function QTYKeyChange(position) {
            if ($('#procedure_qty' + position).val() && $('#procedure_price' + position).val()) {
                $('#total_amount' + position).val(structureMoney("" + $('#procedure_qty' + position).val() * ($('#procedure_price' + position).val().replace(/,/g, ""))))
            } else if (!$('#procedure_qty' + position).val()) {
                $('#total_amount' + position).val("")
            }
        }

        function PriceKeyChange(position) {
            if ($('#procedure_price' + position).val() && $('#procedure_qty' + position).val()) {
                $('#total_amount' + position).val(structureMoney("" + $('#procedure_price' + position).val() * ($('#procedure_qty' + position).val().replace(/,/g, ""))))
            } else if (!$('#procedure_price' + position).val()) {
                $('#total_amount' + position).val("")
            }
        }


        function save_invoice() {
            $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text("{{ __('common.processing') }}");
            $.ajax({
                type: 'POST',
                data: $('#New-invoice-form').serialize(),
                url: "/invoices",
                success: function (data) {
                    $('#New-invoice-modal').modal('hide');
                    $.LoadingOverlay("hide");
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#New-invoice-modal').modal('show');
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text("{{ __('appointment.generate_invoice') }}");

                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function structureMoney(value) {
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }


        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);
            if (status === 'success' && dataTable) {
                dataTable.draw(false);
            }
        }

    </script>
    {{--load appointment calender script via FullCalendar 5.x CDN--}}
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
    @if(app()->getLocale() === 'zh-CN')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/zh-cn.min.js"></script>
    @endif
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: '{{ app()->getLocale() === "zh-CN" ? "zh-cn" : "en" }}',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    editable: false,
                    selectable: true,
                    selectMirror: true,
                    eventDisplay: 'block',
                    events: {
                        url: '{{ url("appointments/calendar-events") }}',
                        method: 'GET',
                        failure: function() {
                            console.error('Failed to load calendar events');
                        }
                    },
                    select: function(info) {
                        var prefill = { date: info.startStr.substring(0, 10) };
                        if (info.view.type !== 'dayGridMonth') {
                            prefill.time = info.startStr.substring(11, 16);
                            var durationMs = info.end - info.start;
                            prefill.duration = Math.round(durationMs / 60000);
                        }
                        if (typeof openAppointmentDrawer === 'function') {
                            openAppointmentDrawer(prefill);
                        }
                        calendar.unselect();
                    },
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                        showAppointmentPopover(info.event, info.jsEvent);
                    }
                });
                window._appointmentCalendar = calendar;

                // --- Appointment Popover Logic (shared by calendar & resource grid) ---
                var $popover = $('#apt-popover');
                window._aptPopoverEventId = null;

                function showAppointmentPopover(event, jsEvent) {
                    var ep = event.extendedProps;
                    window._aptPopoverEventId = event.id;

                    $('#apt-popover-patient').text(ep.patient_name || '');
                    $('#apt-popover-phone').text(ep.patient_phone || '');
                    $('#apt-popover-time').text((ep.start_time || '') + ' - ' + (ep.end_time || ''));
                    $('#apt-popover-doctor').text(ep.doctor_name || '');
                    $('#apt-popover-service').text(ep.service_name || '-');
                    var statusText = ep.status || '';
                    $('#apt-popover-status').text(statusText).css('background-color', event.backgroundColor || '#3a87ad');

                    if (typeof ep.notes !== 'undefined' && ep.notes) {
                        $('#apt-popover-notes').text(ep.notes);
                        $('#apt-popover-notes-row').show();
                    } else {
                        $('#apt-popover-notes-row').hide();
                    }

                    var x = jsEvent.pageX, y = jsEvent.pageY;
                    $popover.css({ top: y + 8, left: x + 8, display: 'block' });

                    setTimeout(function() {
                        var pw = $popover.outerWidth(), ph = $popover.outerHeight();
                        var ww = $(window).width(), wh = $(window).height();
                        var st = $(window).scrollTop(), sl = $(window).scrollLeft();
                        if (x + 8 + pw > sl + ww) $popover.css('left', x - pw - 8);
                        if (y + 8 + ph > st + wh) $popover.css('top', y - ph - 8);
                    }, 0);
                }
                window.showAppointmentPopover = showAppointmentPopover;

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#apt-popover, .fc-event, .drg-event').length) {
                        $popover.hide();
                        window._aptPopoverEventId = null;
                    }
                });

                $('#apt-popover-edit').on('click', function() {
                    var eid = window._aptPopoverEventId;
                    $popover.hide();
                    if (eid && typeof editRecord === 'function') editRecord(eid);
                });
                $('#apt-popover-reschedule').on('click', function() {
                    var eid = window._aptPopoverEventId;
                    $popover.hide();
                    if (eid && typeof RescheduleAppointment === 'function') RescheduleAppointment(eid);
                });
                $('#apt-popover-delete').on('click', function() {
                    var eid = window._aptPopoverEventId;
                    $popover.hide();
                    if (eid && typeof deleteRecord === 'function') deleteRecord(eid);
                });
                $('#apt-popover-sms').on('click', function() {
                    var eid = window._aptPopoverEventId;
                    if (!eid) return;
                    var phone = $('#apt-popover-phone').text();
                    if (!phone) { toastr.warning('{{ __("appointment.no_phone_for_sms") }}'); return; }
                    $popover.hide();
                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        type: 'POST',
                        url: '{{ url("appointments") }}/' + eid + '/send-reminder',
                        data: { _token: CSRF_TOKEN },
                        success: function(res) {
                            if (res.status) toastr.success(res.message);
                            else toastr.error(res.message);
                        },
                        error: function() { toastr.error('{{ __("common.error") }}'); }
                    });
                });
                // Render calendar when tab is shown
                $('a[href="#appointment_calender_tab"]').on('shown.bs.tab', function () {
                    calendar.render();
                });
            }
        });
    </script>
    <link rel="stylesheet" href="{{ asset('css/appointment-resource-grid.css') }}">
    <script src="{{ asset('include_js/appointment_resource_grid.js') }}?v={{ filemtime(public_path('include_js/appointment_resource_grid.js')) }}"></script>
    <script>
        $(function() {
            $('a[href="#doctor_day_view_tab"]').on('shown.bs.tab', function () {
                if (window._drgInstance) window._drgInstance.render();
            });
        });
    </script>
@endsection





