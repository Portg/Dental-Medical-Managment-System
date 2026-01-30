/**
 * Stock In Management JavaScript
 */

var itemsTable;

function loadItems() {
    itemsTable = $('#items-table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "/stock-in-items",
            data: function (d) {
                d.stock_in_id = stockInId;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'item_code', name: 'item_code'},
            {data: 'item_name', name: 'item_name'},
            {data: 'specification', name: 'specification'},
            {data: 'qty', name: 'qty'},
            {data: 'unit_price', name: 'unit_price'},
            {data: 'amount', name: 'amount'},
            {data: 'batch_no', name: 'batch_no'},
            {data: 'expiry_date', name: 'expiry_date'},
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
    var price = $('#item-price').val();
    var batch = $('#item-batch').val();
    var expiry = $('#item-expiry').val();

    if (!itemId || !qty || !price) {
        swal(LanguageManager.trans('common.error') || "Error",
             LanguageManager.trans('inventory.item') + ", " +
             LanguageManager.trans('inventory.quantity') + ", " +
             LanguageManager.trans('inventory.unit_price') + " " +
             LanguageManager.trans('validation.required') || "Item, Quantity and Price are required",
             "error");
        return;
    }

    $.LoadingOverlay("show");
    $.ajax({
        type: 'POST',
        data: {
            _token: csrfToken,
            stock_in_id: stockInId,
            inventory_item_id: itemId,
            qty: qty,
            unit_price: price,
            batch_no: batch,
            expiry_date: expiry
        },
        url: "/stock-in-items",
        success: function (data) {
            $.LoadingOverlay("hide");
            if (data.status === 'warning' && data.requires_confirmation) {
                // Price deviation warning
                swal({
                    title: LanguageManager.trans('common.warning') || "Warning",
                    text: data.message + " (" + data.deviation_percent + "%)",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: LanguageManager.trans('common.confirm') || "Confirm",
                    closeOnConfirm: false
                }, function () {
                    // Re-submit with confirmation
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'POST',
                        data: {
                            _token: csrfToken,
                            stock_in_id: stockInId,
                            inventory_item_id: itemId,
                            qty: qty,
                            unit_price: price,
                            batch_no: batch,
                            expiry_date: expiry,
                            confirm_deviation: true
                        },
                        url: "/stock-in-items",
                        success: function (data2) {
                            $.LoadingOverlay("hide");
                            if (data2.status) {
                                swal(LanguageManager.trans('common.alert') || "Alert", data2.message, "success");
                                itemsTable.ajax.reload();
                                clearItemForm();
                            } else {
                                swal(LanguageManager.trans('common.error') || "Error", data2.message, "error");
                            }
                        }
                    });
                });
            } else if (data.status) {
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
        url: "/stock-in-items/" + id + "/edit",
        success: function (data) {
            $.LoadingOverlay("hide");
            // Show edit modal or inline edit
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
                        qty: inputValue,
                        unit_price: data.unit_price,
                        batch_no: data.batch_no,
                        expiry_date: data.expiry_date
                    },
                    url: "/stock-in-items/" + id,
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
            url: "/stock-in-items/" + id,
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
    $('#item-price').val('');
    $('#item-batch').val('');
    $('#item-expiry').val('');
}

function updateTotal() {
    // Fetch and update total from server
    $.ajax({
        type: 'GET',
        url: "/stock-ins/" + stockInId,
        dataType: 'html',
        success: function (html) {
            // Extract total from response (simple approach)
            // In production, use a dedicated API endpoint
        }
    });
}

function confirmStockIn() {
    swal({
        title: LanguageManager.trans('inventory.confirm_stock_in') || "Confirm Stock In",
        text: LanguageManager.trans('common.are_you_sure') || "Are you sure?",
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
            url: "/stock-ins/" + stockInId + "/confirm",
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal({
                        title: LanguageManager.trans('common.success') || "Success",
                        text: data.message,
                        type: "success"
                    }, function () {
                        window.location.href = "/stock-ins/" + stockInId;
                    });
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            }
        });
    });
}

function cancelStockIn() {
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
            url: "/stock-ins/" + stockInId + "/cancel",
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal({
                        title: LanguageManager.trans('common.success') || "Success",
                        text: data.message,
                        type: "success"
                    }, function () {
                        window.location.href = "/stock-ins";
                    });
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            }
        });
    });
}
