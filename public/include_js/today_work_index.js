/**
 * 今日工作台页面脚本（resources/views/today_work/index.blade.php）。
 *
 * URL 与语言包由 blade 注入：window.TodayWorkIndexConfig / LanguageManager。
 * twTabUrls 仍留在 blade 里，因为 today_work_tabs.js 按那个全局名读取。
 */

var twConfig = window.TodayWorkIndexConfig || {};

var twSearchTimer = null;
var twTable;
var twCurrentView = localStorage.getItem('tw_view_mode') || 'table';


$(document).ready(function() {
    twTable = $('#tw-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: twConfig.dataUrl,
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
            // 操作拆成固定列：流程（主操作）| 病历 | 收费 | 更多
            { data: 'act_flow',    orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_case',    orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_invoice', orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_more',    orderable: false, searchable: false, className: 'tw-col-act' }
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
    $.getJSON(twConfig.statsUrl, params, function(data) {
        $('#kpi-patients').text(data.kpi.today_patients);
        $('#kpi-doctors').text(data.kpi.today_doctors);
        $('#kpi-revisits').text(data.kpi.today_revisits);
        $('#kpi-appointments').text(data.kpi.today_appointments);
        $('#kpi-receivable').html('&yen;' + data.kpi.today_receivable);
        $('#kpi-collected').html('&yen;' + data.kpi.today_collected);

        // 状态分档的计数。stats 接口本来就在算这些数，只是此前没渲染出来 ——
        // 参考视频的「全部(2) | 未到(1) | 已到(1)」，一眼看出还剩几个没到。
        var stats = data.stats || {};
        var total = 0;
        Object.keys(stats).forEach(function (k) {
            $('#pill-' + k).text(stats[k] || 0);
            total += (stats[k] || 0);
        });
        $('#pill-all').text(total);
    });
}

/**
 * 状态分档页签：点一下按该状态筛。
 *
 * 复用既有的 #tw-status-filter（DataTable 已经在读它），这里只是把它从一个
 * 藏起来的下拉换成一排看得见、带计数的按钮。
 */
$(document).on('click', '#tw-status-pills a', function () {
    var status = $(this).data('status');
    $('#tw-status-pills li').removeClass('active');
    $(this).closest('li').addClass('active');
    $('#tw-status-filter').val(status);
    onTodayWorkFilterChanged();
});

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
        utilsScript: twConfig.utilsScript,
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
            validationDiv.textContent = LanguageManager.trans('validation.required', { attribute: LanguageManager.trans('patient.phone_no') });
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
            validationDiv.textContent = LanguageManager.trans('patient.invalid_phone');
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
        language: twConfig.locale,
        placeholder: LanguageManager.trans('patient_tags.select_source'),
        allowClear: true,
        data: data
    });
});

// Select2: patient tags
$.get('/patient-tags-list', function(data) {
    $('#patient_tags').select2({
        language: twConfig.locale,
        placeholder: LanguageManager.trans('patient_tags.select_tags'),
        allowClear: true,
        multiple: true,
        data: data
    });
});

// Select2: insurance company
$('#company').select2({
    language: twConfig.locale,
    placeholder: LanguageManager.trans('patient.choose_insurance_company'),
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
                toastr.error(LanguageManager.trans('common.error_message'));
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
                toastr.error(LanguageManager.trans('common.error_message'));
            }
        }
    });
}


/**
 * 日期前后翻页。delta=0 回到今天。
 *
 * 翻前一天/后一天是每天都在做的动作，只给日历要点三下（开面板、翻月、选日）。
 * 参考视频工作台的「< 2025-10-28 >」。
 */
function shiftTodayWorkDate(delta) {
    var $input = $('#tw-date-filter');
    var base = delta === 0 ? new Date() : new Date(($input.val() || '').replace(/-/g, '/'));
    if (isNaN(base.getTime())) base = new Date();
    if (delta !== 0) base.setDate(base.getDate() + delta);

    var v = base.getFullYear() + '-' +
            ('0' + (base.getMonth() + 1)).slice(-2) + '-' +
            ('0' + base.getDate()).slice(-2);

    // bootstrap-datepicker 只认 changeDate，直接 .val() 不会刷新它自己的状态
    if ($input.data('datepicker')) {
        $input.datepicker('setDate', v);
    } else {
        $input.val(v);
    }
    onTodayWorkFilterChanged();
}
