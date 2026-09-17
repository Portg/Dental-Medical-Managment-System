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
            { data: 'visit_type', orderable: false, searchable: false },
            { data: 'notes', orderable: false, searchable: false },
            // 操作拆成固定列：流程（主操作）| 病历 | 收费 | 更多
            { data: 'act_flow',    orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_case',    orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_invoice', orderable: false, searchable: false, className: 'tw-col-act' },
            { data: 'act_more',    orderable: false, searchable: false, className: 'tw-col-act' }
        ],
        order: [[1, 'asc']],
        pageLength: 50,
        language: $.extend(true, {}, LanguageManager.getDataTableLang(), {
            emptyTable: twEmptyState({
                icon: 'fa-calendar-check-o',
                title: LanguageManager.trans('today_work.list_empty_title', '今日暂无就诊'),
                actionsHtml: twEmptyDayActions(),
                panel: true
            }),
            zeroRecords: twEmptyState({
                icon: 'fa-filter',
                title: LanguageManager.trans('today_work.list_filtered_empty_title', '当前筛选下没有患者'),
                panel: true,
                compact: true
            })
        }),
        dom: 'rtip'
    });

    // Apply saved view preference
    if (twCurrentView === 'kanban') {
        switchView('kanban', true);
    }

    // Initialize info tabs (from today_work_tabs.js)
    initInfoTabs();

    // Load tab count badges + status pill counts (含「已到」聚合)
    loadTabCounts();
    refreshStats();

    // 主区患者搜索：中文输入法下 keyup 常不触发（组字结束无 keyup）。
    // 用 input + compositionend；组字过程中不打请求，避免用半成品拼音去筛。
    (function bindTwPatientSearch() {
        var $input = $('#tw-search');
        if (!$input.length) {
            return;
        }
        var composing = false;
        var run = function () {
            // compositionend 在部分浏览器里值尚未写入，推迟到下一拍
            setTimeout(function () { debounceSearch(); }, 0);
        };
        $input.on('compositionstart', function () { composing = true; });
        $input.on('compositionend', function () {
            composing = false;
            run();
        });
        $input.on('input', function () {
            if (composing) return;
            debounceSearch();
        });
        // 兜底：回车立即搜；非输入法键盘仍可用
        $input.on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                clearTimeout(twSearchTimer);
                if (twCurrentView === 'kanban' && typeof loadKanbanData === 'function') {
                    loadKanbanData();
                } else if (twTable) {
                    twTable.ajax.reload(null, false);
                }
            }
        });
    })();

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
        // 看板视图下状态分档是多余的 —— 卡片的列本身就是状态，
        // 再给一排状态按钮等于同一件事说两遍
        $('#tw-status-pills').hide();
        loadKanbanData();
    } else {
        $('#tw-kanban-view').hide();
        $('#tw-table-view').show();
        $('#btn-kanban-view').removeClass('active');
        $('#tw-status-pills').show();
        $('#btn-table-view').addClass('active');
        $('#kanban-collapse-btn').hide();
        // 状态筛选已经由上面那排带计数的分档按钮承担，这个下拉只留作它的后端载体
        // （分档按钮点一下写它的值）。不再 show —— 同一件事摆两个控件，
        // 用户不知道该信哪个，而且它俩还可能显示不一致。
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
        if (twCurrentView === 'kanban') {
            if (typeof loadKanbanData === 'function') {
                loadKanbanData();
            }
            return;
        }
        if (twTable) {
            twTable.ajax.reload(null, false);
        }
    }, 300);
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
        var kpi = data.kpi || {};
        $('#kpi-new-patients').text(kpi.new_patients != null ? kpi.new_patients : 0);
        $('#kpi-new-appointments').text(kpi.new_appointments != null ? kpi.new_appointments : 0);
        $('#kpi-collected').html('&yen;' + _twMoney(kpi.today_collected));
        $('#kpi-outstanding').html('&yen;' + _twMoney(kpi.outstanding_amount));
        $('#kpi-outstanding-patients').text(
            LanguageManager.trans('today_work.kpi_outstanding_people', { count: kpi.outstanding_patients || 0 })
        );
        $('#kpi-followups').text(kpi.today_followups != null ? kpi.today_followups : 0);
        $('#kpi-visits').text(kpi.today_visits != null ? kpi.today_visits : 0);
        $('#kpi-first-visits').text(
            LanguageManager.trans('today_work.kpi_first_visits', { count: kpi.first_visits || 0 })
        );

        var stats = data.stats || {};
        var total = 0;
        Object.keys(stats).forEach(function (k) {
            $('#pill-' + k).text(stats[k] || 0);
            total += (stats[k] || 0);
        });
        $('#pill-all').text(total);
        var arrived = (stats.waiting || 0) + (stats.called || 0) + (stats.in_treatment || 0);
        $('#pill-arrived').text(arrived);
        $('#badge-today-work').text(total > 0 ? total : '');
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
    $('#btnSavePatient, #btnSaveAndContinue').attr('disabled', true);
    $.ajax({
        type: 'POST',
        data: $('#patient-form').serialize(),
        url: "/patients",
        success: function(data) {
            $.LoadingOverlay("hide");
            $('#btnSavePatient, #btnSaveAndContinue').attr('disabled', false);
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
                    // 建完档紧接着挂号 —— 参考视频，保存患者后直接弹挂号窗口。
                    // 不接这一步的话新患者存完就从台面上消失了：今日就诊列表是从
                    // 预约出的，而他还没有今天的任何预约，前台得再开一次预约抽屉
                    // 把人补回来。新建的一律按初诊。
                    var created = data.data || {};
                    if (created.id && typeof openRegistrationModal === 'function') {
                        openRegistrationModal({
                            patient_id: created.id,
                            patient_name: created.name,
                            appointment_type: 'first_visit',
                            notes: LanguageManager.trans('today_work.new_patient')
                        });
                    }
                }
                refreshStats();
            } else {
                toastr.error(data.message);
            }
        },
        error: function(request) {
            $.LoadingOverlay("hide");
            $('#btnSavePatient, #btnSaveAndContinue').attr('disabled', false);
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
    $('#btnSavePatient').attr('disabled', true);
    $.ajax({
        type: 'PUT',
        data: $('#patient-form').serialize(),
        url: "/patients/" + $('#id').val(),
        success: function(data) {
            $.LoadingOverlay("hide");
            $('#btnSavePatient').attr('disabled', false);
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
            $('#btnSavePatient').attr('disabled', false);
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


/** 金额千位分隔，两位小数 */
function _twMoney(v) {
    return Number(v || 0).toLocaleString('zh-CN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
