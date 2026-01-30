/**
 * Stock Out Management JavaScript
 */

var itemsTable;

function loadItems() {
    itemsTable = $('#items-table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "/stock-out-items",
            data: function (d) {
                d.stock_out_id = stockOutId;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'item_code', name: 'item_code'},
            {data: 'item_name', name: 'item_name'},
            {data: 'specification', name: 'specification'},
            {data: 'current_stock', name: 'current_stock'},
            {data: 'qty', name: 'qty'},
            {data: 'unit_cost', name: 'unit_cost'},
            {data: 'amount', name: 'amount'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        drawCallback: function() {
            updateTotal();
        }
    });
}

function addItem() {
    var itemId = $('#item-select').val();
    var qty = $('#item-qty').val();
    var currentStock = parseFloat($('#item-stock').val()) || 0;

    if (!itemId || !qty) {
        swal(LanguageManager.trans('common.error') || "Error",
             LanguageManager.trans('inventory.item') + " & " +
             LanguageManager.trans('inventory.quantity') + " " +
             LanguageManager.trans('validation.required') || "Item and Quantity are required",
             "error");
        return;
    }

    // Check stock availability
    if (parseFloat(qty) > currentStock) {
        swal(LanguageManager.trans('common.error') || "Error",
             LanguageManager.trans('inventory.insufficient_stock') || "Insufficient stock",
             "error");
        return;
    }

    $.LoadingOverlay("show");
    $.ajax({
        type: 'POST',
        data: {
            _token: csrfToken,
            stock_out_id: stockOutId,
            inventory_item_id: itemId,
            qty: qty
        },
        url: "/stock-out-items",
        success: function (data) {
            $.LoadingOverlay("hide");
            if (data.status) {
                swal(LanguageManager.trans('common.alert') || "Alert", data.message, "success");
                itemsTable.ajax.reload();
                clearItemForm();
            } else {
                swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            if (request.responseJSON && request.responseJSON.errors) {
                var errors = request.responseJSON.errors;
                var message = '';
                $.each(errors, function (key, value) {
                    message += value + '\n';
                });
                swal(LanguageManager.trans('common.error') || "Error", message, "error");
            }
        }
    });
}

function editItem(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: "/stock-out-items/" + id + "/edit",
        success: function (data) {
            $.LoadingOverlay("hide");
            swal({
                title: LanguageManager.trans('common.edit') || "Edit",
                text: LanguageManager.trans('inventory.quantity') || "Quantity",
                type: "input",
                inputValue: data.qty,
                showCancelButton: true,
                closeOnConfirm: false,
                inputPlaceholder: LanguageManager.trans('inventory.quantity') || "Quantity"
            }, function (inputValue) {
                if (inputValue === false) return false;
                if (inputValue === "") {
                    swal.showInputError(LanguageManager.trans('inventory.quantity') + " " + LanguageManager.trans('validation.required'));
                    return false;
                }

                $.LoadingOverlay("show");
                $.ajax({
                    type: 'PUT',
                    data: {
                        _token: csrfToken,
                        qty: inputValue
                    },
                    url: "/stock-out-items/" + id,
                    success: function (response) {
                        $.LoadingOverlay("hide");
                        if (response.status) {
                            swal(LanguageManager.trans('common.alert') || "Alert", response.message, "success");
                            itemsTable.ajax.reload();
                        } else {
                            swal(LanguageManager.trans('common.error') || "Error", response.message, "error");
                        }
                    }
                });
            });
        },
        error: function () {
            $.LoadingOverlay("hide");
        }
    });
}

function deleteItem(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure') || "Are you sure?",
        text: LanguageManager.trans('common.delete_confirm') || "This action cannot be undone!",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it') || "Yes, delete it!",
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay("show");
        $.ajax({
            type: 'DELETE',
            data: { _token: csrfToken },
            url: "/stock-out-items/" + id,
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal(LanguageManager.trans('common.alert') || "Alert", data.message, "success");
                    itemsTable.ajax.reload();
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            }
        });
    });
}

function clearItemForm() {
    $('#item-select').val(null).trigger('change');
    $('#item-qty').val(1);
    $('#item-stock').val('');
}

function updateTotal() {
    // Update total display
}

function confirmStockOut() {
    swal({
        title: LanguageManager.trans('inventory.confirm_stock_out') || "Confirm Stock Out",
        text: LanguageManager.trans('common.are_you_sure') || "Are you sure? Stock will be deducted from inventory.",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-success",
        confirmButtonText: LanguageManager.trans('common.confirm') || "Confirm",
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay("show");
        $.ajax({
            type: 'POST',
            data: { _token: csrfToken },
            url: "/stock-outs/" + stockOutId + "/confirm",
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal({
                        title: LanguageManager.trans('common.success') || "Success",
                        text: data.message,
                        type: "success"
                    }, function () {
                        window.location.href = "/stock-outs/" + stockOutId;
                    });
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            }
        });
    });
}

function cancelStockOut() {
    swal({
        title: LanguageManager.trans('inventory.cancel_record') || "Cancel",
        text: LanguageManager.trans('common.are_you_sure') || "Are you sure?",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.confirm') || "Confirm",
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay("show");
        $.ajax({
            type: 'POST',
            data: { _token: csrfToken },
            url: "/stock-outs/" + stockOutId + "/cancel",
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal({
                        title: LanguageManager.trans('common.success') || "Success",
                        text: data.message,
                        type: "success"
                    }, function () {
                        window.location.href = "/stock-outs";
                    });
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            }
        });
    });
}
