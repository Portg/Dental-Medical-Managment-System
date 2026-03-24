'use strict';

let recordsTable = null;
let kitsTable    = null;

$(document).ready(function () {
    initKitSelectOptions();
    initRecordsTable();
    initKitsTable();
    bindRecordModal();
    bindKitModal();
    bindUseModal();
    bindFilters();
});

/* ── 1. 初始化器械包下拉 ───────────────────────────── */
function initKitSelectOptions() {
    if (typeof sterilizationKits === 'undefined') return;
    const $sel = $('#record-kit-id');
    sterilizationKits.forEach(function (kit) {
        $sel.append(`<option value="${kit.id}">${kit.kit_no} - ${kit.name}</option>`);
    });
    $sel.select2({ width: '100%' });
}

/* ── 2. 灭菌记录 DataTable ───────────────────────── */
function initRecordsTable() {
    recordsTable = $('#records-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/sterilization',
            data: function (d) {
                d.kit_id    = $('#filter-kit-id').val() || null;
                d.status    = $('#filter-status').val() || null;
                d.date_from = $('#filter-date-from').val() || null;
                d.date_to   = $('#filter-date-to').val() || null;
            },
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'batch_no' },
            { data: 'kit_name' },
            { data: 'method_label' },
            { data: 'sterilized_at' },
            { data: 'expires_at' },
            { data: 'operator_name' },
            { data: 'status_badge', orderable: false },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 3. 器械包 DataTable ─────────────────────────── */
function initKitsTable() {
    kitsTable = $('#kits-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/sterilization-kits',
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'kit_no' },
            { data: 'name' },
            { data: 'instruments_count' },
            { data: 'is_active', render: function (v) { return v ? '<span class="badge badge-success">启用</span>' : '<span class="badge badge-secondary">停用</span>'; } },
            { data: 'action', orderable: false },
        ],
        language: { url: '/vendor/datatables/zh-CN.json' },
    });
}

/* ── 4. 筛选条件 ──────────────────────────────────── */
function bindFilters() {
    $('#btn-filter-records').click(function () {
        recordsTable.ajax.reload();
    });
}

/* ── 5. 灭菌记录弹框 ──────────────────────────────── */
function bindRecordModal() {
    $('#btn-add-record').click(function () {
        resetRecordModal();
        $('#record-modal-title').text(LanguageManager.trans('common.add'));
        $('#recordModal').modal('show');
    });

    $('#btn-save-record').click(function () {
        const id  = $('#record-id').val();
        const url = id ? `/sterilization/${id}` : '/sterilization';
        $.ajax({
            url, method: id ? 'PUT' : 'POST',
            data: {
                _token:           $('meta[name="csrf-token"]').attr('content'),
                kit_id:           $('#record-kit-id').val(),
                method:           $('#record-method').val(),
                temperature:      $('#record-temperature').val() || null,
                duration_minutes: $('#record-duration').val() || null,
                sterilized_at:    $('#record-sterilized-at').val(),
                notes:            $('#record-notes').val(),
            },
            success: function (res) {
                if (res.status) {
                    $('#recordModal').modal('hide');
                    recordsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function resetRecordModal() {
    $('#record-id').val('');
    $('#record-kit-id').val(null).trigger('change');
    $('#record-method').val('autoclave');
    $('#record-temperature, #record-duration, #record-notes').val('');
    $('#record-sterilized-at').val(new Date().toISOString().slice(0, 16));
}

function editRecord(id) {
    $.get(`/sterilization/${id}/edit`, function (data) {
        $('#record-id').val(data.id);
        $('#record-kit-id').val(data.kit_id).trigger('change');
        $('#record-method').val(data.method);
        $('#record-temperature').val(data.temperature);
        $('#record-duration').val(data.duration_minutes);
        $('#record-sterilized-at').val(data.sterilized_at ? data.sterilized_at.replace(' ', 'T').slice(0, 16) : '');
        $('#record-notes').val(data.notes);
        $('#record-modal-title').text(LanguageManager.trans('common.edit'));
        $('#recordModal').modal('show');
    });
}

function deleteRecord(id) {
    Swal.fire({ title: '确认删除此灭菌记录?', icon: 'warning', showCancelButton: true })
        .then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sterilization/${id}`, method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.status) { recordsTable.ajax.reload(); toastr.success(res.message); }
                        else { toastr.error(res.message); }
                    },
                });
            }
        });
}

/* ── 6. 登记使用弹框 ──────────────────────────────── */
function bindUseModal() {
    $('#btn-confirm-use').click(function () {
        const id = $('#use-record-id').val();
        $.ajax({
            url: `/sterilization/${id}/use`,
            method: 'POST',
            data: {
                _token:     $('meta[name="csrf-token"]').attr('content'),
                used_at:    $('#use-used-at').val(),
                patient_id: $('#use-patient-id').val() || null,
                notes:      $('#use-notes').val(),
            },
            success: function (res) {
                if (res.status) {
                    $('#useModal').modal('hide');
                    recordsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function logUse(recordId) {
    $.get(`/sterilization/${recordId}/edit`, function (data) {
        $('#use-record-id').val(recordId);
        $('#use-batch-no').text(data.batch_no);
        $('#use-kit-name').text(data.kit_name || '');
        $('#use-used-at').val(new Date().toISOString().slice(0, 16));
        $('#use-patient-id').val(null).trigger('change');
        $('#use-notes').val('');
        $('#useModal').modal('show');
    });
}

/* ── 7. 器械包弹框 ───────────────────────────────── */
function bindKitModal() {
    $('#btn-add-kit').click(function () {
        resetKitModal();
        $('#kit-modal-title').text(LanguageManager.trans('common.add'));
        $('#kitModal').modal('show');
    });

    $('#btn-add-instrument').click(function () {
        addInstrumentRow('', 1);
    });

    $('#btn-save-kit').click(function () {
        const id  = $('#kit-id').val();
        const url = id ? `/sterilization-kits/${id}` : '/sterilization-kits';
        const instruments = [];
        $('#instruments-body tr').each(function () {
            instruments.push({
                instrument_name: $(this).find('.instrument-name').val(),
                quantity:        $(this).find('.instrument-qty').val(),
            });
        });
        $.ajax({
            url, method: id ? 'PUT' : 'POST',
            data: {
                _token:      $('meta[name="csrf-token"]').attr('content'),
                kit_no:      $('#kit-no').val(),
                name:        $('#kit-name').val(),
                instruments: instruments,
            },
            traditional: false,
            success: function (res) {
                if (res.status) {
                    $('#kitModal').modal('hide');
                    kitsTable.ajax.reload();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
        });
    });
}

function resetKitModal() {
    $('#kit-id, #kit-no, #kit-name').val('');
    $('#instruments-body').empty();
}

function addInstrumentRow(name, qty) {
    const row = `<tr>
        <td><input type="text" class="form-control form-control-sm instrument-name" value="${name}"></td>
        <td><input type="number" class="form-control form-control-sm instrument-qty" value="${qty}" min="1"></td>
        <td><button type="button" class="btn btn-xs btn-danger" onclick="$(this).closest('tr').remove()">×</button></td>
    </tr>`;
    $('#instruments-body').append(row);
}

function editKit(id) {
    $.get(`/sterilization-kits/${id}/edit`, function (data) {
        $('#kit-id').val(data.id);
        $('#kit-no').val(data.kit_no);
        $('#kit-name').val(data.name);
        $('#instruments-body').empty();
        (data.instruments || []).forEach(function (inst) {
            addInstrumentRow(inst.instrument_name, inst.quantity);
        });
        $('#kit-modal-title').text(LanguageManager.trans('common.edit'));
        $('#kitModal').modal('show');
    });
}

function deleteKit(id) {
    Swal.fire({ title: '确认删除此器械包?', icon: 'warning', showCancelButton: true })
        .then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/sterilization-kits/${id}`, method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        if (res.status) { kitsTable.ajax.reload(); toastr.success(res.message); }
                        else { toastr.error(res.message); }
                    },
                });
            }
        });
}
