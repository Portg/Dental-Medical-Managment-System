$(function () {
    default_todays_data();

    dataTable = $('#invoices-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.InvoiceIndexConfig.invoicesUrl,
            data: function (d) {
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
            {data: 'invoice_no', name: 'invoice_no', orderable: false},
            {data: 'created_at', name: 'created_at', orderable: false},
            {data: 'customer', name: 'customer', orderable: false},
            {data: 'amount', name: 'amount', orderable: false},
            {data: 'paid_amount', name: 'paid_amount', orderable: false},
            {data: 'due_amount', name: 'due_amount', orderable: false},
            {data: 'addedBy', name: 'addedBy', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function default_todays_data() {
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

$(document).ready(function () {
    $('#company').val([]).trigger('change');
    $("#company").select2("val", "");
    $('.insurance_company').hide();

    $('#self_account_id').val([]).trigger('change');
    $("#self_account_id").select2("val", "");
    $('.self_account').hide();

    $('#cheque_payment').hide();
    $('[name="cheque_no"]').val("");
    $('[name="account_name"]').val("");
    $('[name="bank_name"]').val("");

    $("input[type=radio][name=payment_method]").on("change", function () {
        let action = $("input[type=radio][name=payment_method]:checked").val();

        if (action === "Self Account") {
            $('.self_account').show();
            $('#self_account_id').next(".select2-container").show();
            $('.insurance_company').hide();
            $('#company').next(".select2-container").hide();
            $('#company').val([]).trigger('change');
            $('#cheque_payment').hide();
            $('[name="cheque_no"]').val("");
            $('[name="account_name"]').val("");
            $('[name="bank_name"]').val("");

        } else if (action === "Insurance") {
            $('.insurance_company').show();
            $('#company').next(".select2-container").show();
            $('.self_account').hide();
            $('#self_account_id').next(".select2-container").hide();
            $('#self_account_id').val([]).trigger('change');
            $('#cheque_payment').hide();
            $('[name="cheque_no"]').val("");
            $('[name="account_name"]').val("");
            $('[name="bank_name"]').val("");

        } else if (action === "Cheque") {
            $('#cheque_payment').show();
            $('#company').val([]).trigger('change');
            $('.insurance_company').hide();
            $('#company').next(".select2-container").hide();
            $('.self_account').hide();
            $('#self_account_id').next(".select2-container").hide();
            $('#self_account_id').val([]).trigger('change');

        } else {
            $('#company').val([]).trigger('change');
            $('.insurance_company').hide();
            $('#company').next(".select2-container").hide();
            $('.self_account').hide();
            $('#self_account_id').next(".select2-container").hide();
            $('#self_account_id').val([]).trigger('change');
            $('#cheque_payment').hide();
            $('[name="cheque_no"]').val("");
            $('[name="account_name"]').val("");
            $('[name="bank_name"]').val("");
        }
    });

    $('#self_account_id').select2({
        language: window.InvoiceIndexConfig.locale,
        placeholder: LanguageManager.trans('invoices.choose_self_account'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-self-account',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return { q: $.trim(params.term) };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        }
    });

    $('#company').select2({
        language: window.InvoiceIndexConfig.locale,
        placeholder: LanguageManager.trans('invoices.choose_insurance_company'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-insurance-company',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return { q: $.trim(params.term) };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        }
    });
});

function viewInvoiceProcedures(invoiceId) {
    $('.noResultsText').hide();
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: "invoice-procedures/" + invoiceId,
        success: function (data) {
            if (data.length !== 0) {
                convertJsontoHtmlTable(data);
            } else {
                $('.noResultsText').show();
            }
            $.LoadingOverlay("hide");
            $('#invoice-procedures-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
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

    let invoiceProceduresTable = document.createElement("table");
    invoiceProceduresTable.classList.add("table", "table-striped", "table-bordered", "table-hover");

    let tr = invoiceProceduresTable.insertRow(-1);
    for (let i = 0; i < tablecolumns.length; i++) {
        var th = document.createElement("th");
        th.innerHTML = getTranslatedColumnName(tablecolumns[i]);
        tr.appendChild(th);
    }

    for (let i = 0; i < jsonResponseData.length; i++) {
        tr = invoiceProceduresTable.insertRow(-1);
        for (let j = 0; j < tablecolumns.length; j++) {
            let tabCell = tr.insertCell(-1);
            tabCell.innerHTML = jsonResponseData[i][tablecolumns[j]];
        }
    }

    let invoiceProceduresContainer = document.getElementById("invoiceProceduresContainer");
    invoiceProceduresContainer.innerHTML = "";
    invoiceProceduresContainer.appendChild(invoiceProceduresTable);
}

function getTranslatedColumnName(columnName) {
    const translations = {
        'name':  LanguageManager.trans('invoices.procedure'),
        'qty':   LanguageManager.trans('invoices.quantity'),
        'price': LanguageManager.trans('invoices.unit_price'),
        'total': LanguageManager.trans('invoices.total_amount')
    };
    return translations[columnName] || columnName;
}

function print_invoice() {
    window.print();
}

function record_payment(id) {
    $.LoadingOverlay("show");
    $("#payment-form")[0].reset();
    $('#invoice_id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $.ajax({
        type: 'get',
        url: "invoice-amount/" + id,
        success: function (data) {
            $('#invoice_id').val(id);
            $('[name="amount"]').val(data.amount);
            $('[name="payment_date"]').val(data.today_date);

            if (data.patient != null) {
                let company_data = { id: data.patient.insurance_company_id, text: data.patient.name };
                let newOption = new Option(company_data.text, company_data.id, true, true);
                $('#company').append(newOption).trigger('change');
            }

            $.LoadingOverlay("hide");
            $('#payment-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function save_payment_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#payment-form').serialize(),
        url: "/payments",
        success: function (data) {
            $('#payment-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.save_changes'));
            $('#payment-modal').modal('show');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function shareInvoiceView(invoice_id) {
    $.LoadingOverlay("show");
    $("#share-invoice-form")[0].reset();
    $('#btn-share').attr('disabled', false);
    $('#btn-share').text(LanguageManager.trans('invoices.share_invoice'));
    $.ajax({
        type: 'GET',
        url: "/share-invoice-details/" + invoice_id,
        success: function (data) {
            $.LoadingOverlay("hide");
            $('[name="invoice_id"]').val(data.id);
            $('[name="invoice_no"]').val(data.invoice_no);
            $('[name="name"]').val(LanguageManager.joinName(data.surname, data.othername));
            $('[name="email"]').val(data.email);
            $('#share-invoice-modal').modal('show');
        },
        error: function (xhr, status, error) {
            alert(error);
        }
    });
}

function sendInvoice() {
    $.LoadingOverlay("show");
    $('#btn-share').attr('disabled', true);
    $('#btn-share').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#share-invoice-form').serialize(),
        url: "/share-invoice",
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#share-invoice-modal').modal('hide');
            alert_dialog(data.message, "success");
        },
        error: function (xhr, status, error) {
            $('#btn-share').attr('disabled', false);
            $('#btn-share').text(LanguageManager.trans('invoices.share_invoice'));
            $('#share-invoice-modal').modal('show');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteInvoice(id) {
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('invoices.confirm_delete_invoice'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: false
        },
        function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'DELETE',
                data: { _token: CSRF_TOKEN },
                url: "invoices/" + id,
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
                    alert_dialog(LanguageManager.trans('messages.error_occurred'), "danger");
                }
            });
        });
}
