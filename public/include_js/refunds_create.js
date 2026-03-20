var maxRefundable = 0;

$(document).ready(function() {
    $('#invoice_id').select2({
        placeholder: LanguageManager.trans('invoices.search_invoice'),
        allowClear: true,
        ajax: {
            url: window.RefundsCreateConfig.apiSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.invoice_no + ' - ' + (item.patient_name || '')
                        };
                    })
                };
            }
        }
    });

    if (window.RefundsCreateConfig.preloadInvoiceId) {
        loadInvoiceInfo(window.RefundsCreateConfig.preloadInvoiceId);
    }

    $('#invoice_id').on('change', function() {
        var invoiceId = $(this).val();
        if (invoiceId) {
            loadInvoiceInfo(invoiceId);
        } else {
            $('#invoice_info').hide();
            maxRefundable = 0;
        }
    });

    $('#refund_amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        if (amount > 100) {
            $('#amount_warning').show();
        } else {
            $('#amount_warning').hide();
        }

        if (amount > maxRefundable && maxRefundable > 0) {
            $(this).val(maxRefundable.toFixed(2));
            toastr.warning(LanguageManager.trans('invoices.amount_exceeds_max'));
        }
    });

    $('#refund_form').on('submit', function(e) {
        e.preventDefault();
        submitRefund();
    });
});

function loadInvoiceInfo(invoiceId) {
    $.get(window.RefundsCreateConfig.refundableAmountUrl + '/' + invoiceId, function(data) {
        $('#info_invoice_no').text(data.invoice_no);
        $('#info_paid_amount').text('$' + parseFloat(data.paid_amount).toFixed(2));
        $('#info_refunded_amount').text('$' + parseFloat(data.refunded_amount).toFixed(2));
        $('#info_max_refundable').text('$' + parseFloat(data.max_refundable).toFixed(2));
        maxRefundable = parseFloat(data.max_refundable);
        $('#invoice_info').show();

        if (maxRefundable <= 0) {
            toastr.error(LanguageManager.trans('invoices.no_refundable_amount'));
        }
    });
}

function submitRefund() {
    var formData = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        invoice_id: $('#invoice_id').val(),
        refund_amount: $('#refund_amount').val(),
        refund_method: $('#refund_method').val(),
        refund_reason: $('#refund_reason').val()
    };

    $.ajax({
        url: window.RefundsCreateConfig.refundsUrl,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.status) {
                toastr.success(response.message);
                if (response.needs_approval) {
                    setTimeout(function() {
                        window.location.href = window.RefundsCreateConfig.pendingApprovalsUrl;
                    }, 1500);
                } else {
                    setTimeout(function() {
                        window.location.href = window.RefundsCreateConfig.refundsUrl + '/' + response.refund_id;
                    }, 1500);
                }
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(LanguageManager.trans('messages.error_occurred'));
        }
    });
}
