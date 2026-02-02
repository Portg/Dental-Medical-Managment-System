{{--
    Patient List Page
    Extends the list-page base template
--}}
@extends('layouts.list-page')

{{-- ========================================================================
     Required Sections
     ======================================================================== --}}

@section('page_title')
    {{ __('patient.patient_list') }}
@endsection

@section('table_id', 'patients-table')

@section('table_headers')
    <th>{{ __('patient.id') }}</th>
    <th>{{ __('patient.full_name') }}</th>
    <th>{{ __('patient.gender') }}</th>
    <th>{{ __('patient.phone_no') }}</th>
    <th>{{ __('patient_tags.tags') }}</th>
    <th>{{ __('patient_tags.source') }}</th>
    <th>{{ __('patient.medical_aid') }}</th>
    <th>{{ __('patient.action') }}</th>
@endsection

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <button type="button" class="btn btn-default" onclick="exportPatients()">
        {{ __('common.export') }}
    </button>
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('patient.add_new_patient') }}
    </button>
@endsection

{{-- ========================================================================
     Filter Area (Full Custom)
     ======================================================================== --}}
@section('filter_area')
    {{-- Primary Filters --}}
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('patient.search') }}</div>
            <div class="search-input-wrapper">
                <i class="fa fa-search search-icon"></i>
                <input type="text" id="quickSearch" class="form-control"
                       placeholder="{{ __('patient.search_patients') }}" style="min-width: 200px;">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-label">{{ __('patient_tags.source') }}</div>
            <select id="filter_source" name="filter_source" class="form-control select2" style="width: 100%;"></select>
        </div>
        <div class="col-md-2">
            <div class="filter-label">{{ __('datetime.time_period') }}</div>
            <select class="form-control" id="period_selector">
                <option value="">{{ __('datetime.time_periods.all') }}</option>
                <option value="Today">{{ __('datetime.time_periods.today') }}</option>
                <option value="Yesterday">{{ __('datetime.time_periods.yesterday') }}</option>
                <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
                <option value="Last week">{{ __('datetime.time_periods.last_week') }}</option>
                <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
                <option value="Last Month">{{ __('datetime.time_periods.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-5 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">
                {{ __('common.reset') }}
            </button>
            <button type="button" id="searchBtn" class="btn btn-primary" onclick="doSearch()">
                {{ __('common.search') }}
            </button>
        </div>
    </div>

    {{-- Advanced Filters --}}
    <div id="advancedFilters" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #ebeef5;">
        <div class="row filter-row">
            <div class="col-md-3">
                <div class="filter-label">{{ __('patient.insurance_company') }}</div>
                <select id="filter_company" name="filter_company" class="form-control select2" style="width: 100%;"></select>
            </div>
            <div class="col-md-3">
                <div class="filter-label">{{ __('patient_tags.tags') }}</div>
                <select id="filter_tags" name="filter_tags[]" class="form-control select2" multiple style="width: 100%;">
                </select>
            </div>
            <div class="col-md-6">
                <div class="filter-label">{{ __('datetime.date_range.title') }}</div>
                <div class="date-range-row">
                    <div class="date-input">
                        <input type="text" class="form-control start_date" placeholder="{{ __('datetime.date_range.start_date') }}">
                    </div>
                    <span class="date-separator">{{ __('common.until') }}</span>
                    <div class="date-input">
                        <input type="text" class="form-control end_date" placeholder="{{ __('datetime.date_range.end_date') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Advanced Filter Toggle --}}
    <div class="advanced-filter-toggle">
        <button type="button" id="toggleAdvancedFilters" class="btn btn-link advanced-filter-btn">
            {{ __('common.advanced_filter') }}
        </button>
    </div>
@endsection

{{-- ========================================================================
     Empty State
     ======================================================================== --}}
@section('empty_icon', 'fa-users')

@section('empty_title')
    {{ __('patient.no_patients_found') }}
@endsection

@section('empty_desc')
    {{ __('patient.click_add_patient_to_start') }}
@endsection

@section('empty_action')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('patient.add_new_patient') }}
    </button>
@endsection

{{-- ========================================================================
     Modal Dialogs
     ======================================================================== --}}
@section('modals')
    @include('patients.create')
    @include('patients.patient_history')
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script type="text/javascript">
    // Load page-specific translations
    LanguageManager.loadAllFromPHP({
        'patient': @json(__('patient'))
    });

    // International telephone input
    let input = document.querySelector("#telephone");
    window.intlTelInput(input, {
        onlyCountries: ["cn"],
        initialCountry: "cn",
        autoPlaceholder: "off",
        utilsScript: "{{ asset('backend/assets/global/scripts/utils.js') }}",
    });
    var iti = window.intlTelInputGlobals.getInstance(input);

    // Phone validation on blur
    input.addEventListener('blur', function() {
        validatePhone();
    });

    input.addEventListener('input', function() {
        // Clear validation state on input
        var validationDiv = document.getElementById('phone-validation');
        if (validationDiv) {
            validationDiv.style.display = 'none';
        }
        input.classList.remove('is-invalid', 'is-valid');
    });

    function validatePhone() {
        var validationDiv = document.getElementById('phone-validation');
        var phoneValue = input.value.trim();

        if (!phoneValue) {
            // Phone is required
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            if (validationDiv) {
                validationDiv.textContent = '{{ __("validation.required", ["attribute" => __("patient.phone_no")]) }}';
                validationDiv.className = 'validation-message error';
                validationDiv.style.display = 'block';
            }
            return false;
        }

        // Validate Chinese phone number format (11 digits starting with 1)
        var cleanNumber = phoneValue.replace(/\D/g, '');
        if (cleanNumber.startsWith('86')) {
            cleanNumber = cleanNumber.substring(2);
        }

        if (!/^1[3-9]\d{9}$/.test(cleanNumber)) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            if (validationDiv) {
                validationDiv.textContent = '{{ __("patient.invalid_phone") }}';
                validationDiv.className = 'validation-message error';
                validationDiv.style.display = 'block';
            }
            return false;
        }

        // Valid phone
        input.classList.add('is-valid');
        input.classList.remove('is-invalid');
        if (validationDiv) {
            validationDiv.style.display = 'none';
        }
        return true;
    }

    // ==========================================================================
    // Filter Functions
    // ==========================================================================

    function default_todays_data() {
        $('.start_date').val('');
        $('.end_date').val('');
        $("#period_selector").val('');
    }

    // Period selector change handler
    $('#period_selector').on('change', function() {
        switch (this.value) {
            case 'Today':
                $('.start_date').val(todaysDate());
                $('.end_date').val(todaysDate());
                break;
            case 'Yesterday':
                $('.start_date').val(YesterdaysDate());
                $('.end_date').val(YesterdaysDate());
                break;
            case 'This week':
                $('.start_date').val(thisWeek());
                $('.end_date').val(todaysDate());
                break;
            case 'Last week':
                lastWeek();
                break;
            case 'This Month':
                $('.start_date').val(formatDate(thisMonth()));
                $('.end_date').val(todaysDate());
                break;
            case 'Last Month':
                lastMonth();
                break;
            default:
                $('.start_date').val('');
                $('.end_date').val('');
        }
        doSearch();
    });

    // ==========================================================================
    // DataTable Initialization
    // ==========================================================================

    $(function() {
        default_todays_data();

        // Initialize DataTable
        dataTable = $('#patients-table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/patients/') }}",
                data: function(d) {
                    d.start_date = $('.start_date').val();
                    d.end_date = $('.end_date').val();
                    d.insurance_company = $('#filter_company').val();
                    d.filter_tags = $('#filter_tags').val();
                    d.filter_source = $('#filter_source').val();
                    d.quick_search = $('#quickSearch').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
                {data: 'full_name', name: 'full_name'},
                {data: 'gender', name: 'gender'},
                {data: 'phone_no', name: 'phone_no'},
                {data: 'tags_badges', name: 'tags_badges', orderable: false, searchable: false},
                {data: 'source_name', name: 'source_name'},
                {data: 'medical_insurance', name: 'medical_insurance'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Setup empty state handler
        setupEmptyStateHandler();

        // Quick search with debounce
        $('#quickSearch').on('keyup', debounce(function() {
            dataTable.draw(true);
        }, 300));

        // Auto-filter on select change
        $('#filter_source, #filter_tags').on('change', function() {
            dataTable.draw(true);
        });
    });

    // ==========================================================================
    // Override Base Functions
    // ==========================================================================

    function doSearch() {
        if (dataTable) {
            dataTable.draw(true);
        }
    }

    function clearFilters() {
        default_todays_data();
        $('#quickSearch').val('');
        $('#filter_company').val(null).trigger('change');
        $('#filter_tags').val(null).trigger('change');
        $('#filter_source').val(null).trigger('change');
        doSearch();
    }

    function createRecord() {
        $("#patient-form")[0].reset();
        $('#id').val('');
        $('#btnSave').attr('disabled', false);
        $('#btnSaveAndContinue').attr('disabled', false);
        $('#source_id').val(null).trigger('change');
        $('#patient_tags').val(null).trigger('change');
        $('#company').val([]).trigger('change');
        $('.insurance_company').hide();

        // Reset intl-tel-input
        iti.setNumber('');
        $('#phone_number').val('');

        if (typeof resetPatientFormToCreateMode === 'function') {
            resetPatientFormToCreateMode();
        }
        if (typeof clearHealthInfo === 'function') {
            clearHealthInfo();
        }
        $('#patients-modal').modal('show');
    }

    function editRecord(id) {
        $("#patient-form")[0].reset();
        $('#id').val('');
        $('#btnSave').attr('disabled', false);
        $.LoadingOverlay("show");
        $.ajax({
            type: 'get',
            url: "patients/" + id + "/edit",
            success: function(data) {
                $('#id').val(id);
                $('[name="surname"]').val(data.patient.surname);
                $('[name="othername"]').val(data.patient.othername);
                $('input[name^="gender"][value="' + data.patient.gender + '"').prop('checked', true);
                $('[name="dob"]').val(data.patient.date_of_birth);
                $('[name="email"]').val(data.patient.email);
                $('[name="telephone"]').val(data.patient.phone_no);
                if (data.patient.phone_no != null) {
                    iti.setNumber(data.patient.phone_no);
                }
                $('[name="alternative_no"]').val(data.patient.alternative_no);
                $('[name="nin"]').val(data.patient.nin);
                $('[name="age"]').val(data.patient.age);
                $('[name="profession"]').val(data.patient.profession);
                $('[name="next_of_kin"]').val(data.patient.next_of_kin);
                $('[name="next_of_kin_no"]').val(data.patient.next_of_kin_no);
                $('[name="next_of_kin_address"]').val(data.patient.next_of_kin_address);
                $('[name="address"]').val(data.patient.address);

                // Demographic fields (人口统计信息)
                $('[name="ethnicity"]').val(data.patient.ethnicity);
                $('[name="marital_status"]').val(data.patient.marital_status);
                $('[name="education"]').val(data.patient.education);
                $('[name="blood_type"]').val(data.patient.blood_type);

                $('input[name^="has_insurance"][value="' + data.patient.has_insurance + '"').prop('checked', true);

                if (data.patient.has_insurance == "No") {
                    $('#company').val([]).trigger('change');
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();
                } else {
                    let company_data = {
                        id: data.patient.insurance_company_id,
                        text: data.company
                    };
                    let newOption = new Option(company_data.text, company_data.id, true, true);
                    $('#company').append(newOption).trigger('change');
                    $('.insurance_company').show();
                    $('#company').next(".select2-container").show();
                }

                // Load source
                if (data.patient.source_id && data.source) {
                    let sourceOption = new Option(data.source.name, data.source.id, true, true);
                    $('#source_id').append(sourceOption).trigger('change');
                } else {
                    $('#source_id').val(null).trigger('change');
                }

                // Load tags
                $('#patient_tags').empty();
                if (data.tags && data.tags.length > 0) {
                    data.tags.forEach(function(tag) {
                        let tagOption = new Option(tag.name, tag.id, true, true);
                        $('#patient_tags').append(tagOption);
                    });
                    $('#patient_tags').trigger('change');
                }

                // Load additional fields
                $('[name="medication_history"]').val(data.patient.medication_history || '');
                $('[name="notes"]').val(data.patient.notes || '');

                if (typeof populateHealthInfo === 'function') {
                    populateHealthInfo(data.patient);
                }
                if (typeof setPatientFormToEditMode === 'function') {
                    setPatientFormToEditMode(data.patient);
                }

                $.LoadingOverlay("hide");
                $('#patients-modal').modal('show');
            },
            error: function() {
                $.LoadingOverlay("hide");
            }
        });
    }

    function deleteRecord(id) {
        var sweetAlertLang = LanguageManager.getSweetAlertLang();
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('patient.delete_patient_warning') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: sweetAlertLang.cancel,
            closeOnConfirm: false
        }, function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "patients/" + id,
                success: function(data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function() {
                    $.LoadingOverlay("hide");
                }
            });
        });
    }

    function exportPatients() {
        let params = {};
        let start_date = $('.start_date').val();
        let end_date = $('.end_date').val();
        let insurance_company = $('#filter_company').val();
        let filter_tags = $('#filter_tags').val();
        let filter_source = $('#filter_source').val();
        let quick_search = $('#quickSearch').val();

        if (start_date) params.start_date = start_date;
        if (end_date) params.end_date = end_date;
        if (insurance_company) params.insurance_company = insurance_company;
        if (filter_tags && filter_tags.length > 0) params.filter_tags = filter_tags;
        if (filter_source) params.filter_source = filter_source;
        if (quick_search) params.quick_search = quick_search;

        let queryString = $.param(params);
        window.location.href = '/export-patients?' + queryString;
    }

    // ==========================================================================
    // Form CRUD Functions
    // ==========================================================================

    function save_data(continueAdding) {
        // Validate phone before saving
        if (!validatePhone()) {
            input.focus();
            return;
        }

        var id = $('#id').val();
        let number = iti.getNumber();
        $('#phone_number').val(number);

        if (id === "") {
            save_new_record(continueAdding);
        } else {
            update_record();
        }
    }

    function save_new_record(continueAdding) {
        $.LoadingOverlay("show");
        $('#btnSave').attr('disabled', true);
        $('#btnSaveAndContinue').attr('disabled', true);
        $('#btnSave').html('{{ __("common.saving") }}');
        $.ajax({
            type: 'POST',
            data: $('#patient-form').serialize(),
            url: "/patients",
            success: function(data) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSaveAndContinue').attr('disabled', false);
                $('#btnSave').html('{{ __("common.save") }}');

                if (data.status) {
                    if (continueAdding) {
                        toastr.success(data.message);
                        var currentSource = $('#source_id').val();
                        $("#patient-form")[0].reset();
                        $('#id').val('');
                        $('#patient_tags').val(null).trigger('change');
                        $('#company').val([]).trigger('change');
                        $('.insurance_company').hide();
                        // Reset intl-tel-input
                        iti.setNumber('');
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
                        dataTable.draw(false);
                    } else {
                        $('#patients-modal').modal('hide');
                        alert_dialog(data.message, "success");
                    }
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSaveAndContinue').attr('disabled', false);
                $('#btnSave').html('{{ __("common.save") }}');
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btnSave').attr('disabled', true);
        $('#btnSave').text("{{ __('common.updating') }}");
        $.ajax({
            type: 'PUT',
            data: $('#patient-form').serialize(),
            url: "/patients/" + $('#id').val(),
            success: function(data) {
                $('#patients-modal').modal('hide');
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSave').text("{{ __('common.update_record') }}");
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function(key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    // ==========================================================================
    // Additional Functions
    // ==========================================================================

    function patientHistory(patient_id) {
        $('#patient-history-modal').modal('show');
    }

    function getPatientMedicalHistory(patient_id) {
        $.LoadingOverlay("show");
        $('.noResultsText').hide();
        $.ajax({
            type: 'get',
            url: "patients/" + patient_id + "/medicalHistory",
            success: function(data) {
                $('.patientInfoText').text(LanguageManager.joinName(data.patientInfor.surname, data.patientInfor.othername));
                if (data.treatmentHistory.length != 0) {
                    convertJsontoHtmlTable(data.treatmentHistory);
                } else {
                    $('.noResultsText').show();
                }
                $.LoadingOverlay("hide");
                $('#patient-history-modal').modal('show');
            },
            error: function() {
                $.LoadingOverlay("hide");
                $('#patient-history-modal').modal('hide');
            }
        });
    }

    function convertJsontoHtmlTable(jsonResponseData) {
        var tablecolumns = [];
        for (var i = 0; i < jsonResponseData.length; i++) {
            for (var key in jsonResponseData[i]) {
                if (tablecolumns.indexOf(key) === -1) {
                    tablecolumns.push(key);
                }
            }
        }

        var treatmentHistoryTable = document.createElement("table");
        treatmentHistoryTable.classList.add("table", "table-striped", "table-bordered", "table-hover");

        var tr = treatmentHistoryTable.insertRow(-1);
        for (var i = 0; i < tablecolumns.length; i++) {
            var th = document.createElement("th");
            th.innerHTML = tablecolumns[i];
            tr.appendChild(th);
        }

        for (var i = 0; i < jsonResponseData.length; i++) {
            tr = treatmentHistoryTable.insertRow(-1);
            for (var j = 0; j < tablecolumns.length; j++) {
                var tabCell = tr.insertCell(-1);
                tabCell.innerHTML = jsonResponseData[i][tablecolumns[j]];
            }
        }

        var patientHistoryContainer = document.getElementById("patientHistoryContainer");
        patientHistoryContainer.innerHTML = "";
        patientHistoryContainer.appendChild(treatmentHistoryTable);
    }

    // ==========================================================================
    // Select2 Initializations (for modal form)
    // ==========================================================================

    // Filter insurance companies
    $('#filter_company').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('patient.choose_insurance_company') }}",
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '/search-insurance-company',
            dataType: 'json',
            data: function(params) {
                return { q: $.trim(params.term) };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });

    // Filter source - 与保险公司相同的初始化方式
    $('#filter_source').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('patient_tags.select_source') }}",
        allowClear: true,
        ajax: {
            url: '/patient-sources-list',
            dataType: 'json',
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });

    // Filter tags - 多选标签
    $('#filter_tags').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('patient_tags.select_tags') }}",
        allowClear: true,
        multiple: true,
        ajax: {
            url: '/patient-tags-list',
            dataType: 'json',
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });

    // Form insurance company select
    $('#company').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('patient.choose_insurance_company') }}",
        minimumInputLength: 2,
        ajax: {
            url: '/search-insurance-company',
            dataType: 'json',
            data: function(params) {
                return { q: $.trim(params.term) };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        }
    });

    // Source dropdown
    $.get('/patient-sources-list', function(data) {
        $('#source_id').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('patient_tags.select_source') }}",
            allowClear: true,
            data: data
        });
    });

    // Tags multi-select
    $.get('/patient-tags-list', function(data) {
        $('#patient_tags').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('patient_tags.select_tags') }}",
            allowClear: true,
            multiple: true,
            data: data
        });
    });

    // Insurance company toggle in form
    $(document).ready(function() {
        $('.insurance_company').hide();
        $("input[type=radio][name=has_insurance]").on("change", function() {
            let action = $("input[type=radio][name=has_insurance]:checked").val();
            if (action == "No") {
                $('#company').val([]).trigger('change');
                $('.insurance_company').hide();
                $('#company').next(".select2-container").hide();
            } else {
                $('.insurance_company').show();
                $('#company').next(".select2-container").show();
            }
        });
    });
</script>
@endsection
