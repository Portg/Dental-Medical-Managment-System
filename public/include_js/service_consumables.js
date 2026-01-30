/**
 * Service Consumables Configuration JavaScript
 */

var table;

$(function () {
    loadTable();

    // Initialize Select2 for item search
    $('.select2-item').select2({
        ajax: {
            url: '/inventory-items-search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: LanguageManager.trans('inventory.select_item') || "Select Item"
    });
});

function loadTable() {
    table = $('#consumables-table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "/service-consumables",
            data: function (d) {
                d.medical_service_id = $('#filter-service').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'service_name', name: 'service_name'},
            {data: 'item_code', name: 'item_code'},
            {data: 'item_name', name: 'item_name'},
            {data: 'unit', name: 'unit'},
            {data: 'qty', name: 'qty'},
            {data: 'is_required_label', name: 'is_required_label', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function filterTable() {
    table.ajax.reload();
}

function addConsumable() {
    var formData = $('#consumable-form').serialize();

    $.LoadingOverlay("show");
    $.ajax({
        type: 'POST',
        data: formData,
        url: "/service-consumables",
        success: function (data) {
            $.LoadingOverlay("hide");
            if (data.status) {
                swal(LanguageManager.trans('common.alert') || "Alert", data.message, "success");
                table.ajax.reload();
                $('#consumable-form')[0].reset();
                $('#item-select').val(null).trigger('change');
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
            type: 'DELETE',
            data: { _token: CSRF_TOKEN },
            url: "/service-consumables/" + id,
            success: function (data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal(LanguageManager.trans('common.alert') || "Alert", data.message, "success");
                    table.ajax.reload();
                } else {
                    swal(LanguageManager.trans('common.error') || "Error", data.message, "error");
                }
            },
            error: function () {
                $.LoadingOverlay("hide");
            }
        });
    });
}
