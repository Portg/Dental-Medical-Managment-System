var dataTable;
var labCaseItemIndex = 0;

$(function () {
    dataTable = $('#lab-cases-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: labCaseUrls.index,
            data: function (d) {
                d.status = $('#filter_status').val();
                d.lab_id = $('#filter_lab').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'lab_case_no', name: 'lab_case_no'},
            {data: 'patient_name', name: 'patient_name', orderable: false, searchable: false},
            {data: 'doctor_name', name: 'doctor_name', orderable: false, searchable: false},
            {data: 'lab_name', name: 'lab_name', orderable: false, searchable: false},
            {data: 'prosthesis_type_label', name: 'prosthesis_type', orderable: false},
            {data: 'status_label', name: 'status', orderable: false},
            {data: 'expected_return_date', name: 'expected_return_date'},
            {data: 'overdue_flag', name: 'overdue_flag', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();

    $('#filterBtn').click(function () {
        dataTable.draw(true);
    });

    // Status modal: toggle conditional fields
    $('#status_value').on('change', function () {
        var val = $(this).val();
        $('#sent_date_group').toggle(val === 'sent');
        if (val === 'rework') {
            $('#rework_reason_group').show();
        } else {
            $('#rework_reason_group').hide();
            $('#status_rework_reason').val('');
        }
    });

    // Patient search select2
    $('#create_patient_id').select2({
        language: labCaseUrls.locale,
        placeholder: LanguageManager.trans('lab_cases.select_patient'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-patient',
            dataType: 'json',
            delay: 300,
            data: function (params) { return { q: $.trim(params.term) }; },
            processResults: function (data) { return { results: data }; },
            cache: true
        }
    });

    // Doctor search select2
    $('#create_doctor_id').select2({
        language: labCaseUrls.locale,
        placeholder: LanguageManager.trans('lab_cases.select_doctor'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-doctor',
            dataType: 'json',
            delay: 300,
            data: function (params) { return { q: $.trim(params.term) }; },
            processResults: function (data) { return { results: data }; },
            cache: true
        }
    });

    // Lab change → auto-fill processing_days + show lab info + recalc expected date
    $(document).on('change', '#create_lab_id', function () {
        onLabChange($(this), '#create_processing_days', '#create_sent_date', '#create_expected_return_date', 'create');
    });

    // Processing days / sent_date change → recalc expected date
    $(document).on('change', '#create_processing_days, #create_sent_date', function () {
        calcExpectedDate('#create_sent_date', '#create_processing_days', '#create_expected_return_date');
    });
    $(document).on('change', '#edit_processing_days, #edit_expected_return_date_trigger', function () {
        calcExpectedDate('#edit_sent_date_display', '#edit_processing_days', '#edit_expected_return_date');
    });
});

// ─── Create ──────────────────────────────────────────────────
function createLabCase() {
    $("#create-lab-case-form")[0].reset();
    $('#create_patient_id').val(null).trigger('change');
    $('#create_doctor_id').val(null).trigger('change');
    $('#create_lab_info_box').hide();
    $('.alert-danger').hide().find('ul').html('');
    // Reset items: clear all rows, add one default row
    $('#create-item-rows').empty();
    labCaseItemIndex = 0;
    addItemRow('create');
    $('#create-lab-case-modal').modal('show');
}

function saveLabCase() {
    $.LoadingOverlay("show");
    $('#btn-create').attr('disabled', true).text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#create-lab-case-form').serialize(),
        url: labCaseUrls.store,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#btn-create').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            $('#create-lab-case-modal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-create').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

// ─── Edit ────────────────────────────────────────────────────
function editLabCase(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'GET',
        url: labCaseUrls.apiGet + '/' + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#edit_id').val(data.id);
            $('#edit_processing_days').val(data.processing_days);
            $('#edit_special_requirements').val(data.special_requirements);
            $('#edit_expected_return_date').val(data.expected_return_date ? data.expected_return_date.substring(0, 10) : '');
            $('#edit_lab_fee').val(data.lab_fee);
            $('#edit_patient_charge').val(data.patient_charge);
            $('#edit_quality_rating').val(data.quality_rating);
            $('#edit_notes').val(data.notes);
            // Populate items
            populateEditItems(data.items || []);
            $('.alert-danger').hide().find('ul').html('');
            $('#edit-lab-case-modal').modal('show');
        },
        error: function () {
            $.LoadingOverlay("hide");
        }
    });
}

function updateLabCase() {
    var id = $('#edit_id').val();
    $.LoadingOverlay("show");
    $('#btn-update').attr('disabled', true).text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'PUT',
        data: $('#edit-lab-case-form').serialize(),
        url: labCaseUrls.update + '/' + id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#btn-update').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            $('#edit-lab-case-modal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-update').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

// ─── Status ──────────────────────────────────────────────────
function updateStatus(id) {
    $('#status_case_id').val(id);
    $('#status_value').val('');
    $('#sent_date_group').hide();
    $('#rework_reason_group').hide();
    $('#status_rework_reason').val('');
    $('.alert-danger').hide().find('ul').html('');
    $('#status-modal').modal('show');
}

function saveStatus() {
    var id = $('#status_case_id').val();
    $.LoadingOverlay("show");
    $('#btn-status').attr('disabled', true).text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#status-form').serialize(),
        url: labCaseUrls.updateStatus.replace('__ID__', id),
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#btn-status').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            $('#status-modal').modal('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
            if (data.status) {
                dataTable.draw(false);
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-status').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            var json = $.parseJSON(request.responseText);
            var errors = '';
            $.each(json.errors || {}, function (key, value) {
                errors += '<li>' + value + '</li>';
            });
            $('.alert-danger').show().find('ul').html(errors);
        }
    });
}

// ─── Delete ──────────────────────────────────────────────────
function deleteLabCase(id) {
    swal({
        title: LanguageManager.trans('lab_cases.are_you_sure'),
        text: LanguageManager.trans('lab_cases.confirm_delete_case'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('lab_cases.yes_delete_it'),
        cancelButtonText: LanguageManager.trans('lab_cases.cancel'),
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'DELETE',
            data: {_token: CSRF_TOKEN},
            url: labCaseUrls.destroy + '/' + id,
            success: function (data) {
                $.LoadingOverlay("hide");
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? "success" : "error");
                if (data.status) {
                    dataTable.draw(false);
                }
            },
            error: function () {
                $.LoadingOverlay("hide");
            }
        });
    });
}

// ─── Item Row Management (Table-based) ───────────────────────
function addItemRow(prefix) {
    var container = $('#' + prefix + '-item-rows');
    var count = container.find('.item-row').length;
    if (count >= 4) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('lab_cases.max_items_reached'), 'warning');
        return;
    }

    var idx = labCaseItemIndex++;
    var rowNum = count + 1;
    var html = buildItemRowHtml(idx, rowNum);
    container.append(html);
}

function removeItemRow(btn) {
    var container = $(btn).closest('.item-rows-container');
    $(btn).closest('.item-row').remove();
    // Re-number rows
    container.find('.item-row').each(function(i) {
        $(this).find('.item-row-num').text(i + 1);
    });
    // Ensure at least 1 row remains
    if (container.find('.item-row').length === 0) {
        var prefix = container.attr('id').replace('-item-rows', '');
        addItemRow(prefix);
    }
}

function buildItemRowHtml(idx, rowNum) {
    var prosthesisOptions = '<option value="">--</option>';
    $.each(labCaseData.prosthesisTypes, function(key, label) {
        prosthesisOptions += '<option value="' + key + '">' + label + '</option>';
    });

    var materialOptions = '<option value="">--</option>';
    $.each(labCaseData.materials, function(key, label) {
        materialOptions += '<option value="' + key + '">' + label + '</option>';
    });

    return '<tr class="item-row">' +
        '<td class="item-row-num">' + rowNum + '</td>' +
        '<td><select name="items[' + idx + '][prosthesis_type]" class="form-control input-sm">' + prosthesisOptions + '</select></td>' +
        '<td><select name="items[' + idx + '][material]" class="form-control input-sm">' + materialOptions + '</select></td>' +
        '<td><input type="text" name="items[' + idx + '][color_shade]" class="form-control input-sm" placeholder="A2"></td>' +
        '<td><input type="text" name="items[' + idx + '][teeth_positions]" class="form-control input-sm" placeholder="11, 12"></td>' +
        '<td><input type="number" name="items[' + idx + '][qty]" class="form-control input-sm input-qty" value="1" min="1" max="99"></td>' +
        '<td><span class="btn-remove-row" onclick="removeItemRow(this)"><i class="fa fa-times-circle"></i></span></td>' +
    '</tr>';
}

function populateEditItems(items) {
    var container = $('#edit-item-rows');
    container.empty();
    labCaseItemIndex = 0;

    if (items.length === 0) {
        addItemRow('edit');
        return;
    }

    $.each(items, function(i, item) {
        var idx = labCaseItemIndex++;
        var html = buildItemRowHtml(idx, i + 1);
        container.append(html);

        var row = container.find('.item-row').last();
        row.find('select[name="items[' + idx + '][prosthesis_type]"]').val(item.prosthesis_type);
        row.find('select[name="items[' + idx + '][material]"]').val(item.material);
        row.find('input[name="items[' + idx + '][color_shade]"]').val(item.color_shade);
        row.find('input[name="items[' + idx + '][teeth_positions]"]').val(
            item.teeth_positions ? (Array.isArray(item.teeth_positions) ? item.teeth_positions.join(', ') : item.teeth_positions) : ''
        );
        row.find('input[name="items[' + idx + '][qty]"]').val(item.qty || 1);
    });
}

// ─── Lab Info Display ────────────────────────────────────────
function showLabInfo(selectEl, infoPrefix) {
    var option = selectEl.find('option:selected');
    var infoBox = $('#' + infoPrefix + '_lab_info_box');

    if (option.val()) {
        $('#' + infoPrefix + '_lab_info_contact').text(option.data('contact') || '-');
        $('#' + infoPrefix + '_lab_info_phone').text(option.data('phone') || '-');
        $('#' + infoPrefix + '_lab_info_specialties').text(option.data('specialties') || '-');
        $('#' + infoPrefix + '_lab_info_turnaround').text(option.data('turnaround') || '-');
        infoBox.show();
    } else {
        infoBox.hide();
    }
}

// ─── Date Calculation ────────────────────────────────────────
function calcExpectedDate(sentDateSel, processingDaysSel, expectedDateSel) {
    var sentDate = $(sentDateSel).val();
    var days = parseInt($(processingDaysSel).val());

    if (sentDate && days > 0) {
        var d = new Date(sentDate);
        d.setDate(d.getDate() + days);
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        $(expectedDateSel).val(yyyy + '-' + mm + '-' + dd);
    }
}

function onLabChange(selectEl, processingDaysSel, sentDateSel, expectedDateSel, infoPrefix) {
    var option = selectEl.find('option:selected');
    var turnaround = option.data('turnaround');
    if (turnaround) {
        $(processingDaysSel).val(turnaround);
        calcExpectedDate(sentDateSel, processingDaysSel, expectedDateSel);
    }
    // Show lab info reference
    showLabInfo(selectEl, infoPrefix);
}
