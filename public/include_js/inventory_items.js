/**
 * Inventory Items Management JavaScript
 */

var table;

$(function () {
    loadTable();
});

function loadTable() {
    table = $('#items-table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "/inventory-items",
            data: function (d) {
                d.category_id = $('#filter-category').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'item_code', name: 'item_code'},
            {data: 'name', name: 'name'},
            {data: 'specification', name: 'specification'},
            {data: 'unit', name: 'unit'},
            {data: 'category_name', name: 'category_name'},
            {data: 'current_stock', name: 'current_stock'},
            {data: 'stock_status', name: 'stock_status', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function filterTable() {
    table.ajax.reload();
}

function createRecord() {
    $("#item-form")[0].reset();
    $('#id').val('');
    $('[name="is_active"]').prop('checked', true);
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes') || 'Save Changes');
    $('.alert-danger').hide();
    $('#item-modal').modal('show');
}

function save_data() {
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
    $('#btn-save').text(LanguageManager.trans('common.processing') || 'Processing...');
    $('.alert-danger').hide().find('ul').empty();

    $.ajax({
        type: 'POST',
        data: $('#item-form').serialize(),
        url: "/inventory-items",
        success: function (data) {
            $('#item-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.save_changes') || 'Save Changes');

            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                $.each(errors, function (key, value) {
                    $('.alert-danger ul').append('<li>' + value + '</li>');
                });
                $('.alert-danger').show();
            }
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#item-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('.alert-danger').hide();

    $.ajax({
        type: 'get',
        url: "inventory-items/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            $('[name="item_code"]').val(data.item_code);
            $('[name="name"]').val(data.name);
            $('[name="specification"]').val(data.specification);
            $('[name="unit"]').val(data.unit);
            $('[name="category_id"]').val(data.category_id);
            $('[name="brand"]').val(data.brand);
            $('[name="reference_price"]').val(data.reference_price);
            $('[name="selling_price"]').val(data.selling_price);
            $('[name="stock_warning_level"]').val(data.stock_warning_level);
            $('[name="storage_location"]').val(data.storage_location);
            $('[name="notes"]').val(data.notes);
            $('[name="track_expiry"]').prop('checked', data.track_expiry);
            $('[name="is_active"]').prop('checked', data.is_active);

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record') || 'Update Record');
            $('#item-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating') || 'Updating...');
    $('.alert-danger').hide().find('ul').empty();

    $.ajax({
        type: 'PUT',
        data: $('#item-form').serialize(),
        url: "/inventory-items/" + $('#id').val(),
        success: function (data) {
            $('#item-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.save_changes') || 'Save Changes');

            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                $.each(errors, function (key, value) {
                    $('.alert-danger ul').append('<li>' + value + '</li>');
                });
                $('.alert-danger').show();
            }
        }
    });
}

function deleteRecord(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure') || "Are you sure?",
        text: LanguageManager.trans('common.delete_confirm') || "This action cannot be undone!",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it') || "Yes, delete it!",
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: "/inventory-items/" + id,
            success: function (data) {
                if (data.status) {
                    alert_dialog(data.message, "success");
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

function alert_dialog(message, status) {
    swal(LanguageManager.trans('common.alert') || "Alert", message, status);
    if (status === "success") {
        table.ajax.reload();
    }
}
