$(function () {
    $('#sample_1').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: '/claims-payment/' + $('#global_claim_id').val(),
            data: function (d) {
            }
        },
        dom: 'Bfrtip',
        buttons: {
            buttons: [
                // {extend: 'pdfHtml5', className: 'pdfButton'},
                // {extend: 'excelHtml5', className: 'excelButton'},
            ]
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
            {data: 'payment_date', name: 'payment_date'},
            {data: 'amount', name: 'amount'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
});


function editRecord(id) {
    save = false;
    $.LoadingOverlay("show");
    $("#payment-form")[0].reset();
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "/claims-payment/" + id + "/edit",
        success: function (data) {
            $('#claim_id').val(id);
            $('[name="payment_date"]').val(data.payment_date);
            $('[name="amount"]').val(data.amount);

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#payment-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

// update the payment info
function save_payment_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#payment-form').serialize(),
        url: "/claims-payment/" + $('#claim_id').val(),
        success: function (data) {
            $('#payment-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $.LoadingOverlay("hide");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteRecord(id) {
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('doctor_claim.payments.delete_payment_confirm'),
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
                data: {
                    _token: CSRF_TOKEN
                },
                url: "/claims-payment/" + id,
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
    if (status) {
        let oTable = $('#sample_1').dataTable();
        oTable.fnDraw(false);
        swal(LanguageManager.trans('common.alert'), message, status);
    }
}
