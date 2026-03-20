/* global LanguageManager, ExpensesConfig */

let suppliers_ary = [];
let expense_categories_arry = [];

function default_todays_data() {
    // initially load today's date filtered data
    $('#filter_start_date').val(todaysDate());
    $('#filter_end_date').val(todaysDate());
    $("#period_selector").val('Today');
}

function clearCustomFilters() {
    $('#period_selector').val('');
    $('#filter_start_date').val('');
    $('#filter_end_date').val('');
}

$('#period_selector').on('change', function () {
    switch (this.value) {
        case 'Today':
            $('#filter_start_date').val(todaysDate());
            $('#filter_end_date').val(todaysDate());
            break;
        case 'Yesterday':
            $('#filter_start_date').val(YesterdaysDate());
            $('#filter_end_date').val(YesterdaysDate());
            break;
        case 'This week':
            $('#filter_start_date').val(thisWeek());
            $('#filter_end_date').val(todaysDate());
            break;
        case 'Last week':
            lastWeek();
            break;
        case 'This Month':
            $('#filter_start_date').val(formatDate(thisMonth()));
            $('#filter_end_date').val(todaysDate());
            break;
        case 'Last Month':
            lastMonth();
            break;
    }
    doSearch();
});

$(function () {
    default_todays_data();
    dataTable = $('#expenses-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: ExpensesConfig.expensesUrl,
            data: function (d) {
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
            {data: 'purchase_date', name: 'purchase_date'},
            {data: 'supplier_name', name: 'supplier_name'},
            {data: 'amount', name: 'amount'},
            {data: 'paid_amount', name: 'paid_amount'},
            {data: 'due_amount', name: 'due_amount'},
            {data: 'added_by', name: 'added_by'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

$(document).ready(function () {
    $.ajax({
        type: 'get',
        url: "/filter-suppliers",
        success: function (data) {
            suppliers_ary = JSON.parse(data);
        }
    }).done(function () {
        $("#supplier").typeahead({
            source: suppliers_ary,
            minLength: 1
        });
    });

    // get expense items array
    $.ajax({
        type: 'get',
        url: "/expense-categories-array",
        success: function (data) {
            expense_categories_arry = JSON.parse(data);
        }
    }).done(function () {
        $("#item").typeahead({
            source: expense_categories_arry,
            minLength: 1
        });
    });

    $('#qty').on('keyup change', function () {
        if ($(this).val() && $('#price-single-unit').val()) {
            $('#total_amount').val(structureMoney("" + $(this).val() * ($('#price-single-unit').val().replace(/,/g, ""))));
        } else if (!$(this).val()) {
            $('#total_amount').val("");
        }
    });

    $('#price-single-unit').on('keyup change', function () {
        if ($(this).val() && $('#qty').val()) {
            $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#qty').val()));
        } else if (!$(this).val()) {
            $('#total_amount').val("");
        }
    });
});

function buildExpenseOptions() {
    let html = '<option value="">' + LanguageManager.trans('expenses.choose_expense_category') + '</option>';
    (ExpensesConfig.chartOfAccts || []).forEach(function (cat) {
        html += '<option value="' + cat.id + '">' + cat.name + '</option>';
    });
    return html;
}

function createRecord() {
    $("#purchase-form")[0].reset();
    $('#id').val('');
    $('[name="purchase_date"]').val(todaysDate());
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#purchase-modal').modal('show');
}

$(document).on('click', '.remove-tr', function () {
    $(this).parents('tr').remove();
});

let i = 0;
$("#add").click(function () {
    ++i;

    let expenseOptions = buildExpenseOptions();
    let value = '<select id="select2-single-input-group-sm" class="form-control select2" name="addmore[' + i + '][expense_category]">' +
        expenseOptions +
        '</select>';

    $("#purchasesTable").append(
        '<tr>' +
        '<td><input type="text" id="item_append' + i + '" name="addmore[' + i + '][item]" placeholder="' + LanguageManager.trans('expenses.enter_item') + '" class="form-control"/></td>' +
        '<td><input type="text" id="description' + i + '" name="addmore[' + i + '][description]" placeholder="' + LanguageManager.trans('expenses.enter_description') + '" class="form-control"/></td>' +
        '<td>' + value + '</td>' +
        '<td><input type="number" id="qty' + i + '" name="addmore[' + i + '][qty]" placeholder="' + LanguageManager.trans('expenses.enter_quantity') + '" class="form-control"/></td>' +
        '<td><input type="number" id="price-single-unit' + i + '" name="addmore[' + i + '][price]" placeholder="' + LanguageManager.trans('expenses.enter_unit_price') + '" class="form-control"/></td>' +
        '<td><input type="text" id="total_amount' + i + '" readonly placeholder="' + LanguageManager.trans('expenses.enter_total_amount') + '" class="form-control"/></td>' +
        '<td><button type="button" class="btn btn-danger remove-tr">' + LanguageManager.trans('common.remove') + '</button></td>' +
        '</tr>'
    );

    $("#item_append" + i).typeahead({
        source: expense_categories_arry,
        minLength: 1
    });

    $('#qty' + i).on('keyup change', function () {
        if ($(this).val() && $('#price-single-unit' + i).val()) {
            $('#total_amount' + i).val(structureMoney("" + $(this).val() * ($('#price-single-unit' + i).val().replace(/,/g, ""))));
        } else if (!$(this).val()) {
            $('#total_amount' + i).val("");
        }
    });

    $('#price-single-unit' + i).on('keyup change', function () {
        if ($(this).val() && $('#qty' + i).val()) {
            $('#total_amount' + i).val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#qty' + i).val()));
        } else if (!$(this).val()) {
            $('#total_amount' + i).val("");
        }
    });
});

function structureMoney(value) {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function save_purchase() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#purchase-form').serialize(),
        url: "/expenses",
        success: function (data) {
            $('#purchase-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('expenses.save_purchase'));
            $('#purchase-modal').modal('show');
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
            title: LanguageManager.trans('messages.are_you_sure'),
            text: LanguageManager.trans('messages.cannot_recover_expense'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            closeOnConfirm: false
        },
        function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: {_token: CSRF_TOKEN},
                url: "/expenses/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function () {
                    $.LoadingOverlay("hide");
                }
            });
        });
}

function RecordPayment(expense_id) {
    $.LoadingOverlay("show");
    $("#payment-form")[0].reset();
    $('#expense_id').val('');
    $('#btnSave').attr('disabled', false);
    $('#btnSave').text(LanguageManager.trans('common.save_record'));

    $.ajax({
        type: 'get',
        url: "purchase-balance/" + expense_id,
        success: function (data) {
            $('#expense_id').val(expense_id);
            $('[name="amount"]').val(data.amount);
            $('[name="payment_date"]').val(data.today_date);
            $.LoadingOverlay("hide");
            $('#payment-modal').modal('show');
        },
        error: function () {
            $.LoadingOverlay("hide");
        }
    });
}

function save_payment_record() {
    $.LoadingOverlay("show");
    $('#btnSave').attr('disabled', true);
    $('#btnSave').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#payment-form').serialize(),
        url: "/expense-payments",
        success: function (data) {
            $('#payment-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request) {
            $.LoadingOverlay("hide");
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text(LanguageManager.trans('common.save_record'));
            $('#payment-modal').modal('show');
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}
