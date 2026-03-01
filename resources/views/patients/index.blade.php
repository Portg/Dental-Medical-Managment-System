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
    <th class="col-checkbox"><input type="checkbox" id="selectAll"></th>
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
     Left Sidebar (Patient Groups)
     ======================================================================== --}}
@section('left_sidebar')
    <div class="patient-group-panel" style="background:#fff; border:1px solid #e7ecf1; border-radius:4px; padding:12px;">
        <div style="font-size:14px; font-weight:600; margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #f0f0f0;">
            {{ __('patient.patient_group') }}
        </div>

        <div class="group-list">
            <a href="javascript:;" class="group-item active" data-group="" style="display:flex; justify-content:space-between; padding:6px 8px; border-radius:3px; margin-bottom:2px; color:#333; text-decoration:none; font-size:13px;">
                <span>{{ __('common.all') }}</span>
                <span class="badge" style="background:#eee; color:#666;">{{ $totalCount }}</span>
            </a>

            @foreach($allGroups as $g)
                <a href="javascript:;" class="group-item" data-group="{{ $g->code }}" style="display:flex; justify-content:space-between; padding:6px 8px; border-radius:3px; margin-bottom:2px; color:#555; text-decoration:none; font-size:13px;">
                    <span>{{ $g->name }}</span>
                    <span class="badge" style="background:#eee; color:#666;">{{ $groupCounts[$g->code] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @if($tagCounts->count())
        <div style="font-size:13px; font-weight:600; margin:14px 0 8px; padding-top:10px; border-top:1px solid #f0f0f0; color:#666;">
            {{ __('patient.tags') }}
        </div>
        <div class="tag-list">
            @foreach($tagCounts as $tc)
                <a href="javascript:;" class="tag-filter-item" data-tag-id="{{ $tc->id }}" style="display:flex; justify-content:space-between; padding:4px 8px; border-radius:3px; margin-bottom:2px; color:#555; text-decoration:none; font-size:12px;">
                    <span>{{ $tc->name }}</span>
                    <span class="badge" style="background:#eee; color:#666; font-size:11px;">{{ $tc->cnt }}</span>
                </a>
            @endforeach
        </div>
        @endif
    </div>
@endsection

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <div class="btn-group" id="batchActions" style="display:none;">
        <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
            {{ __('patient.batch_actions') }} <span class="badge batch-count">0</span> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            <li><a href="javascript:;" onclick="batchSetTags()">{{ __('patient.batch_set_tags') }}</a></li>
            <li><a href="javascript:;" onclick="batchSetGroup()">{{ __('patient.batch_set_group') }}</a></li>
            <li><a href="javascript:;" onclick="batchRemoveGroup()">{{ __('patient.batch_remove_group') }}</a></li>
            <li class="divider"></li>
            <li id="mergeMenuItem" class="disabled"><a href="javascript:;" onclick="mergePatients()">{{ __('patient.merge_patients') }}</a></li>
        </ul>
    </div>
    <button type="button" class="btn btn-default" onclick="exportPatients()">
        {{ __('common.export') }}
    </button>
    <button type="button" class="btn btn-default" onclick="$('#importModal').modal('show')">
        <i class="fa fa-upload"></i> {{ __('patient.import') }}
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
        <div class="row filter-row" style="margin-top:10px;">
            <div class="col-md-3">
                <div class="filter-label">{{ __('patient.age_range') }}</div>
                <div class="input-group input-group-sm">
                    <input type="number" id="filter_age_min" class="form-control" min="0" max="120" placeholder="{{ __('patient.min_placeholder') }}">
                    <span class="input-group-addon">~</span>
                    <input type="number" id="filter_age_max" class="form-control" min="0" max="120" placeholder="{{ __('patient.max_placeholder') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="filter-label">{{ __('patient.spending_range') }}</div>
                <div class="input-group input-group-sm">
                    <input type="number" id="filter_spend_min" class="form-control" min="0" step="0.01" placeholder="{{ __('patient.min_amount') }}">
                    <span class="input-group-addon">~</span>
                    <input type="number" id="filter_spend_max" class="form-control" min="0" step="0.01" placeholder="{{ __('patient.max_amount') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="filter-label">{{ __('patient.first_visit_doctor') }}</div>
                <select id="filter_doctor" class="form-control select2" style="width:100%;"></select>
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

    {{-- Batch Tags Modal --}}
    <div class="modal fade" id="batchTagsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">{{ __('patient.batch_set_tags') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('patient_tags.tags') }}</label>
                        <select id="batch_tag_ids" class="form-control select2" multiple style="width:100%;"></select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('patient.batch_tag_mode') }}</label>
                        <div class="radio">
                            <label><input type="radio" name="batch_tag_mode" value="append" checked> {{ __('patient.batch_tag_append') }}</label>
                        </div>
                        <div class="radio">
                            <label><input type="radio" name="batch_tag_mode" value="replace"> {{ __('patient.batch_tag_replace') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="submitBatchTags()">{{ __('common.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Batch Group Modal --}}
    <div class="modal fade" id="batchGroupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">{{ __('patient.batch_set_group') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('patient.patient_group') }}</label>
                        @foreach($allGroups as $g)
                            <div class="radio">
                                <label><input type="radio" name="batch_group_code" value="{{ $g->code }}"> {{ $g->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="submitBatchGroup()">{{ __('common.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Merge Patients Modal --}}
    <div class="modal fade" id="mergeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" style="display:inline-block;">{{ __('patient.merge_patients') }}</h4>
                    <button type="button" class="btn btn-sm btn-default pull-right" onclick="swapMergePrimary()" style="margin-top:-2px;">
                        <i class="fa fa-exchange"></i> {{ __('patient.merge_swap') }}
                    </button>
                </div>
                <div class="modal-body" id="mergePreviewBody">
                    <div class="text-center" style="padding:40px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmMerge" onclick="submitMerge()">
                        <i class="fa fa-compress"></i> {{ __('common.confirm') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Patients Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">{{ __('patient.import_patients') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>1. {{ __('patient.download_template') }}</label>
                        <div>
                            <a href="{{ url('patients/import-template') }}" class="btn btn-sm btn-default">
                                <i class="fa fa-download"></i> {{ __('patient.download_template') }}
                            </a>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>2. {{ __('patient.upload_file') }}</label>
                        <input type="file" id="importFile" class="form-control" accept=".xlsx,.xls,.csv">
                        <p class="help-block">{{ __('patient.import_supported_formats') }}</p>
                    </div>
                    <div class="alert alert-info" style="font-size:13px; margin-bottom:10px;">
                        <ul style="padding-left:18px; margin-bottom:0;">
                            <li>{{ __('patient.import_hint_required') }}</li>
                            <li>{{ __('patient.import_hint_header') }}</li>
                            <li>{{ __('patient.import_hint_limit') }}</li>
                        </ul>
                    </div>
                    <div id="importResultArea" style="display:none;">
                        <hr>
                        <h5>{{ __('patient.import_result') }}</h5>
                        <div id="importResultContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitImport" onclick="submitImport()">
                        <i class="fa fa-upload"></i> {{ __('patient.import_start') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
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
        // initially load today's date filtered data
        $('.start_date').val(todaysDate());
        $('.end_date').val(todaysDate());
        $("#period_selector").val('Today');
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
            processing: true,
            serverSide: true,
            order: [],
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
                    d.filter_group = window._activeGroup || '';
                    d.filter_sidebar_tag = window._activeSidebarTag || '';
                    d.filter_age_min = $('#filter_age_min').val();
                    d.filter_age_max = $('#filter_age_max').val();
                    d.filter_spend_min = $('#filter_spend_min').val();
                    d.filter_spend_max = $('#filter_spend_max').val();
                    d.filter_doctor = $('#filter_doctor').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'col-checkbox'},
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
        $('#filter_age_min, #filter_age_max, #filter_spend_min, #filter_spend_max').val('');
        $('#filter_doctor').val(null).trigger('change');
        doSearch();
    }

    function createRecord() {
        $("#patient-form")[0].reset();
        $('#id').val('');
        $('#btnSave').attr('disabled', false);
        $('#btnSaveAndContinue').attr('disabled', false);
        $('#source_id').val(null).trigger('change');
        $('#company').val([]).trigger('change');
        $('.insurance_company').hide();

        // Reset intl-tel-input
        iti.setNumber('');
        $('#phone_number').val('');

        // Reset referred_by
        if (typeof setReferredBy === 'function') setReferredBy(null);

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

        // Reset left panel state
        if (typeof resetAvatar === 'function') resetAvatar();
        if (typeof clearKinRelations === 'function') clearKinRelations();
        document.querySelectorAll('#left-panel-tags input[type="checkbox"]').forEach(function(cb) { cb.checked = false; });
        var noneRadio = document.querySelector('input[name="patient_group"][value=""]');
        if (noneRadio) noneRadio.checked = true;
        // Reset referred_by Select2
        if (typeof setReferredBy === 'function') setReferredBy(null);

        $.LoadingOverlay("show");
        $.ajax({
            type: 'get',
            url: "patients/" + id + "/edit",
            success: function(data) {
                var p = data.patient;
                $('#id').val(id);

                // Name fields (locale-adaptive)
                if ($('[name="full_name"]').length) {
                    // zh-CN mode: combine surname + othername into full_name
                    var fullName = (p.surname || '') + (p.othername || '');
                    $('[name="full_name"]').val(fullName);
                } else {
                    $('[name="surname"]').val(p.surname);
                    $('[name="othername"]').val(p.othername);
                }

                // Basic fields
                $('input[name="gender"][value="' + p.gender + '"]').prop('checked', true);
                $('[name="dob"]').val(p.date_of_birth);
                $('[name="email"]').val(p.email);
                $('[name="nin"]').val(p.nin);
                $('[name="address"]').val(p.address);

                // Phone (intl-tel-input)
                $('[name="telephone"]').val(p.phone_no);
                if (p.phone_no != null) {
                    iti.setNumber(p.phone_no);
                }

                // Demographics
                $('[name="age"]').val(p.age);
                $('[name="profession"]').val(p.profession);
                $('[name="ethnicity"]').val(p.ethnicity);
                $('[name="marital_status"]').val(p.marital_status);
                $('[name="education"]').val(p.education);
                $('[name="blood_type"]').val(p.blood_type);

                // Emergency contact
                $('[name="alternative_no"]').val(p.alternative_no);
                $('[name="next_of_kin"]').val(p.next_of_kin);
                $('[name="next_of_kin_no"]').val(p.next_of_kin_no);
                $('[name="next_of_kin_address"]').val(p.next_of_kin_address);

                // Insurance
                $('input[name="has_insurance"][value="' + (p.has_insurance ? '1' : '0') + '"]').prop('checked', true);
                if (!p.has_insurance) {
                    $('#company').val([]).trigger('change');
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();
                } else {
                    let newOption = new Option(data.company, p.insurance_company_id, true, true);
                    $('#company').append(newOption).trigger('change');
                    $('.insurance_company').show();
                    $('#company').next(".select2-container").show();
                }

                // Source (Select2)
                if (p.source_id && data.source) {
                    let sourceOption = new Option(data.source.name, data.source.id, true, true);
                    $('#source_id').append(sourceOption).trigger('change');
                } else {
                    $('#source_id').val(null).trigger('change');
                }

                // Tags (left panel checkboxes)
                if (data.tags && data.tags.length > 0) {
                    var tagIds = data.tags.map(function(tag) { return tag.id; });
                    if (typeof setLeftPanelTags === 'function') {
                        setLeftPanelTags(tagIds);
                    }
                }

                // Patient group (left panel radio)
                if (p.patient_group) {
                    var groupRadio = document.querySelector('input[name="patient_group"][value="' + p.patient_group + '"]');
                    if (groupRadio) groupRadio.checked = true;
                }

                // Avatar/photo
                if (p.photo && typeof setAvatarFromUrl === 'function') {
                    setAvatarFromUrl('/storage/' + p.photo);
                }

                // Referred by (Select2)
                if (p.referred_by && p.referrer) {
                    if (typeof setReferredBy === 'function') {
                        var refName = p.referrer.full_name || ((p.referrer.surname || '') + (p.referrer.othername || ''));
                        setReferredBy(p.referred_by, refName);
                    }
                }

                // Kin relations
                if (typeof loadKinRelations === 'function') {
                    loadKinRelations(p.shared_holders || []);
                }

                // Other text fields
                $('[name="medication_history"]').val(p.medication_history || '');
                $('[name="notes"]').val(p.notes || '');

                // Health info checkboxes
                if (typeof populateHealthInfo === 'function') {
                    populateHealthInfo(p);
                }

                // Expand relevant sections
                if (typeof setPatientFormToEditMode === 'function') {
                    setPatientFormToEditMode(p);
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

    // ========================================================================
    // Patient Import
    // ========================================================================

    function submitImport() {
        var fileInput = $('#importFile')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            toastr.warning(LanguageManager.trans('patient.import_file_required'));
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        var btn = $('#btnSubmitImport');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + LanguageManager.trans('patient.import_processing'));
        $('#importResultArea').hide();

        $.ajax({
            url: '/patients/import',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa fa-upload"></i> ' + LanguageManager.trans('patient.import_start'));
                showImportResult(res);

                if (res.status === 1) {
                    // Refresh DataTable
                    table.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa fa-upload"></i> ' + LanguageManager.trans('patient.import_start'));
                toastr.error(xhr.responseJSON?.message || 'Import failed');
            }
        });
    }

    function showImportResult(res) {
        var html = '';
        var data = res.data || {};

        if (data.success > 0) {
            html += '<div class="text-success" style="margin-bottom:8px;"><i class="fa fa-check-circle"></i> '
                  + LanguageManager.trans('patient.import_success_count', {count: data.success}) + '</div>';
        }

        if (data.failures && data.failures.length > 0) {
            html += '<div class="text-danger" style="margin-bottom:6px;"><i class="fa fa-times-circle"></i> '
                  + LanguageManager.trans('patient.import_fail_count', {count: data.failures.length}) + '</div>';
            html += '<ul style="font-size:12px; color:#c0392b; max-height:200px; overflow-y:auto; padding-left:18px;">';
            data.failures.forEach(function(f) {
                var rowLabel = f.row > 0 ? LanguageManager.trans('patient.import_row_error', {row: f.row}) + ': ' : '';
                html += '<li>' + rowLabel + f.errors.join('; ') + '</li>';
            });
            html += '</ul>';
        }

        if (!data.success && (!data.failures || data.failures.length === 0)) {
            html = '<div class="text-warning">' + (res.message || 'No data') + '</div>';
        }

        $('#importResultContent').html(html);
        $('#importResultArea').show();
    }

    // Reset import modal on close
    $('#importModal').on('hidden.bs.modal', function() {
        $('#importFile').val('');
        $('#importResultArea').hide();
        $('#importResultContent').html('');
    });

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

        var formData = buildPatientFormData();

        $.ajax({
            type: 'POST',
            data: formData,
            url: "/patients",
            processData: false,
            contentType: false,
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

        var formData = buildPatientFormData();
        formData.append('_method', 'PUT');

        $.ajax({
            type: 'POST',
            data: formData,
            url: "/patients/" + $('#id').val(),
            processData: false,
            contentType: false,
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
            delay: 300,
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
            delay: 300,
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
            delay: 300,
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
            delay: 300,
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

    // Tags are now loaded as checkboxes in left panel via loadLeftPanelTags()

    // Insurance company toggle in form
    $(document).ready(function() {
        $('.insurance_company').hide();
        $("input[type=radio][name=has_insurance]").on("change", function() {
            let action = $("input[type=radio][name=has_insurance]:checked").val();
            if (action == "0") {
                $('#company').val([]).trigger('change');
                $('.insurance_company').hide();
                $('#company').next(".select2-container").hide();
            } else {
                $('.insurance_company').show();
                $('#company').next(".select2-container").show();
            }
        });
    });

    // ==========================================================================
    // Left Sidebar Group/Tag Click Handlers
    // ==========================================================================

    window._activeGroup = '';
    window._activeSidebarTag = '';

    $(document).on('click', '.group-item', function() {
        $('.group-item').css({'background': '', 'color': '#555'}).removeClass('active');
        $(this).css({'background': '#3598dc', 'color': '#fff'}).addClass('active');
        window._activeGroup = $(this).data('group');
        window._activeSidebarTag = '';
        $('.tag-filter-item').css({'background': '', 'color': '#555'});
        doSearch();
    });

    $(document).on('click', '.tag-filter-item', function() {
        $('.tag-filter-item').css({'background': '', 'color': '#555'});
        $('.group-item').css({'background': '', 'color': '#555'}).removeClass('active');

        if (window._activeSidebarTag == $(this).data('tag-id')) {
            window._activeSidebarTag = '';
            $('.group-item[data-group=""]').css({'background': '#3598dc', 'color': '#fff'}).addClass('active');
        } else {
            $(this).css({'background': '#3598dc', 'color': '#fff'});
            window._activeSidebarTag = $(this).data('tag-id');
            window._activeGroup = '';
        }
        doSearch();
    });

    // ==========================================================================
    // Doctor Filter Select2
    // ==========================================================================

    $('#filter_doctor').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('patient.first_visit_doctor') }}",
        allowClear: true,
        ajax: {
            url: '/search-doctor',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; }
        }
    });

    // ==========================================================================
    // Batch Operations — Checkbox Management
    // ==========================================================================

    function getSelectedIds() {
        var ids = [];
        $('.patient-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    function updateBatchActions() {
        var count = getSelectedIds().length;
        if (count > 0) {
            $('#batchActions').show();
            $('.batch-count').text(count);
        } else {
            $('#batchActions').hide();
            $('.batch-count').text(0);
        }
        // Merge requires exactly 2 patients
        if (count === 2) {
            $('#mergeMenuItem').removeClass('disabled').find('a').css({'color': '', 'pointer-events': ''});
        } else {
            $('#mergeMenuItem').addClass('disabled').find('a').css({'color': '#999', 'pointer-events': 'none'});
        }
    }

    $(document).on('change', '#selectAll', function() {
        var checked = $(this).is(':checked');
        $('.patient-checkbox').prop('checked', checked);
        updateBatchActions();
    });

    $(document).on('change', '.patient-checkbox', function() {
        var allChecked = $('.patient-checkbox').length === $('.patient-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
        updateBatchActions();
    });

    // Reset selectAll when DataTable redraws
    if (dataTable) {
        dataTable.on('draw', function() {
            $('#selectAll').prop('checked', false);
            updateBatchActions();
        });
    }

    // ==========================================================================
    // Batch Operations — Tags
    // ==========================================================================

    // Initialize batch tags Select2
    $('#batchTagsModal').on('shown.bs.modal', function() {
        $('#batch_tag_ids').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('patient_tags.select_tags') }}",
            allowClear: true,
            multiple: true,
            dropdownParent: $('#batchTagsModal'),
            ajax: {
                url: '/patient-tags-list',
                dataType: 'json',
                delay: 300,
                processResults: function(data) { return { results: data }; },
                cache: true
            }
        });
    });

    function batchSetTags() {
        var ids = getSelectedIds();
        if (!ids.length) return toastr.warning(LanguageManager.trans('common.please_select_checkbox'));
        $('#batch_tag_ids').val(null).trigger('change');
        $('input[name="batch_tag_mode"][value="append"]').prop('checked', true);
        $('#batchTagsModal').modal('show');
    }

    function submitBatchTags() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.post('/patients/batch-tags', {
            _token: csrfToken,
            patient_ids: getSelectedIds(),
            tag_ids: $('#batch_tag_ids').val(),
            mode: $('input[name="batch_tag_mode"]:checked').val()
        }, function(resp) {
            if (resp.status) {
                toastr.success(resp.message);
                dataTable.draw(false);
            }
            $('#batchTagsModal').modal('hide');
        });
    }

    // ==========================================================================
    // Batch Operations — Group
    // ==========================================================================

    function batchSetGroup() {
        var ids = getSelectedIds();
        if (!ids.length) return toastr.warning(LanguageManager.trans('common.please_select_checkbox'));
        $('input[name="batch_group_code"]').prop('checked', false);
        $('#batchGroupModal').modal('show');
    }

    function submitBatchGroup() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var groupCode = $('input[name="batch_group_code"]:checked').val();
        if (!groupCode) return toastr.warning(LanguageManager.trans('common.please_select'));
        $.post('/patients/batch-group', {
            _token: csrfToken,
            patient_ids: getSelectedIds(),
            group_code: groupCode
        }, function(resp) {
            if (resp.status) {
                toastr.success(resp.message);
                dataTable.draw(false);
            }
            $('#batchGroupModal').modal('hide');
        });
    }

    function batchRemoveGroup() {
        var ids = getSelectedIds();
        if (!ids.length) return toastr.warning(LanguageManager.trans('common.please_select_checkbox'));
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        swal({
            title: LanguageManager.trans('patient.batch_confirm_remove_group'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-warning',
            confirmButtonText: LanguageManager.trans('common.confirm'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: true
        }, function() {
            $.post('/patients/batch-group', {
                _token: csrfToken,
                patient_ids: ids,
                group_code: null
            }, function(resp) {
                if (resp.status) {
                    toastr.success(resp.message);
                    dataTable.draw(false);
                }
            });
        });
    }

    // ==========================================================================
    // Patient Merge
    // ==========================================================================

    var _mergeData = null; // stores the preview data from server

    function mergePatients() {
        var ids = getSelectedIds();
        if (ids.length !== 2) {
            return toastr.warning(LanguageManager.trans('patient.merge_select_exactly_two'));
        }

        // Show modal with loading spinner
        $('#mergePreviewBody').html('<div class="text-center" style="padding:40px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
        $('#mergeModal').modal('show');

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.post('/patients/merge-preview', {
            _token: csrfToken,
            patient_a: ids[0],
            patient_b: ids[1]
        }, function(resp) {
            if (resp.status) {
                _mergeData = resp.data;
                renderMergePreview(_mergeData);
            }
        }).fail(function() {
            $('#mergePreviewBody').html('<div class="alert alert-danger">' + LanguageManager.trans('patient.merge_failed') + '</div>');
        });
    }

    function renderMergePreview(data) {
        var primary = data.patient_a;
        var secondary = data.patient_b;
        var fields = data.compare_fields;

        var html = '';

        // Patient cards row
        html += '<div class="row">';
        html += '<div class="col-md-6">';
        html += '<div class="panel panel-success"><div class="panel-heading"><strong>' + LanguageManager.trans('patient.merge_primary') + '</strong></div>';
        html += '<div class="panel-body">';
        html += '<p><strong>' + primary.full_name + '</strong> (' + primary.patient_no + ')</p>';
        html += '<p><i class="fa fa-phone"></i> ' + (primary.phone_no || '-') + '</p>';
        html += renderCountBadges(primary.counts);
        html += '</div></div></div>';

        html += '<div class="col-md-6">';
        html += '<div class="panel panel-warning"><div class="panel-heading"><strong>' + LanguageManager.trans('patient.merge_secondary') + '</strong></div>';
        html += '<div class="panel-body">';
        html += '<p><strong>' + secondary.full_name + '</strong> (' + secondary.patient_no + ')</p>';
        html += '<p><i class="fa fa-phone"></i> ' + (secondary.phone_no || '-') + '</p>';
        html += renderCountBadges(secondary.counts);
        html += '</div></div></div>';
        html += '</div>';

        // Field comparison
        html += '<h5 style="border-bottom:1px solid #eee; padding-bottom:8px; margin-top:10px;">';
        html += '<i class="fa fa-columns"></i> ' + LanguageManager.trans('patient.merge_field_compare') + '</h5>';

        if (fields.length === 0) {
            html += '<p class="text-muted text-center" style="padding:15px;">' + LanguageManager.trans('patient.merge_no_diff') + '</p>';
        } else {
            html += '<table class="table table-condensed table-striped" style="margin-bottom:10px;">';
            html += '<thead><tr><th style="width:25%;">' + LanguageManager.trans('patient.merge_field_compare') + '</th>';
            html += '<th style="width:37%;">' + LanguageManager.trans('patient.merge_primary') + '</th>';
            html += '<th style="width:38%;">' + LanguageManager.trans('patient.merge_secondary') + '</th></tr></thead><tbody>';
            for (var i = 0; i < fields.length; i++) {
                var f = fields[i];
                var nameA = 'merge_field_' + f.field + '_a';
                var nameB = 'merge_field_' + f.field + '_b';
                html += '<tr>';
                html += '<td>' + f.label + '</td>';
                html += '<td><label><input type="radio" name="merge_field_' + f.field + '" value="primary" checked> ' + escapeHtml(f.value_a || '-') + '</label></td>';
                html += '<td><label><input type="radio" name="merge_field_' + f.field + '" value="secondary"> ' + escapeHtml(f.value_b || '-') + '</label></td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
        }

        // Migration warning
        var totalRelated = secondary.counts.appointments + secondary.counts.invoices +
            secondary.counts.cases + secondary.counts.images + secondary.counts.followups;
        if (totalRelated > 0) {
            html += '<div class="alert alert-warning" style="margin-bottom:0;">';
            html += '<i class="fa fa-exclamation-triangle"></i> <strong>' + LanguageManager.trans('patient.merge_related_data') + ':</strong> ';
            var parts = [];
            if (secondary.counts.appointments > 0) parts.push(secondary.counts.appointments + ' ' + LanguageManager.trans('patient.merge_appointments'));
            if (secondary.counts.invoices > 0) parts.push(secondary.counts.invoices + ' ' + LanguageManager.trans('patient.merge_invoices'));
            if (secondary.counts.cases > 0) parts.push(secondary.counts.cases + ' ' + LanguageManager.trans('patient.merge_cases'));
            if (secondary.counts.images > 0) parts.push(secondary.counts.images + ' ' + LanguageManager.trans('patient.merge_images'));
            if (secondary.counts.followups > 0) parts.push(secondary.counts.followups + ' ' + LanguageManager.trans('patient.merge_followups'));
            html += parts.join('、');
            html += '<br><small>' + LanguageManager.trans('patient.merge_warning') + '</small>';
            html += '</div>';
        } else {
            html += '<div class="alert alert-info" style="margin-bottom:0;">';
            html += '<i class="fa fa-info-circle"></i> ' + LanguageManager.trans('patient.merge_warning');
            html += '</div>';
        }

        $('#mergePreviewBody').html(html);
    }

    function renderCountBadges(counts) {
        var html = '<div style="margin-top:5px;">';
        html += '<span class="label label-default" style="margin-right:4px;">' + LanguageManager.trans('patient.merge_appointments') + ' ' + counts.appointments + '</span>';
        html += '<span class="label label-default" style="margin-right:4px;">' + LanguageManager.trans('patient.merge_invoices') + ' ' + counts.invoices + '</span>';
        html += '<span class="label label-default" style="margin-right:4px;">' + LanguageManager.trans('patient.merge_cases') + ' ' + counts.cases + '</span>';
        html += '<span class="label label-default" style="margin-right:4px;">' + LanguageManager.trans('patient.merge_images') + ' ' + counts.images + '</span>';
        html += '<span class="label label-default">' + LanguageManager.trans('patient.merge_followups') + ' ' + counts.followups + '</span>';
        html += '</div>';
        return html;
    }

    function swapMergePrimary() {
        if (!_mergeData) return;
        var tmp = _mergeData.patient_a;
        _mergeData.patient_a = _mergeData.patient_b;
        _mergeData.patient_b = tmp;
        // Swap field values too
        for (var i = 0; i < _mergeData.compare_fields.length; i++) {
            var f = _mergeData.compare_fields[i];
            var tmpVal = f.value_a;
            f.value_a = f.value_b;
            f.value_b = tmpVal;
        }
        renderMergePreview(_mergeData);
    }

    function submitMerge() {
        if (!_mergeData) return;

        var primaryId = _mergeData.patient_a.id;
        var secondaryId = _mergeData.patient_b.id;

        // Collect field overrides (where user chose secondary value)
        var fieldOverrides = {};
        for (var i = 0; i < _mergeData.compare_fields.length; i++) {
            var f = _mergeData.compare_fields[i];
            var chosen = $('input[name="merge_field_' + f.field + '"]:checked').val();
            if (chosen === 'secondary') {
                fieldOverrides[f.field] = f.value_b;
            }
        }

        $('#btnConfirmMerge').prop('disabled', true);
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.post('/patients/merge', {
            _token: csrfToken,
            primary_id: primaryId,
            secondary_id: secondaryId,
            field_overrides: fieldOverrides
        }, function(resp) {
            $('#btnConfirmMerge').prop('disabled', false);
            $('#mergeModal').modal('hide');
            if (resp.status) {
                toastr.success(resp.message);
                dataTable.draw(false);
            } else {
                toastr.error(resp.message);
            }
        }).fail(function() {
            $('#btnConfirmMerge').prop('disabled', false);
            toastr.error(LanguageManager.trans('patient.merge_failed'));
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
</script>
@endsection
