let _appointment_id = $('#global_appointment_id').val();
$("#dental_billing_tab_link").on("click", function () {
    load_dental_billing();
});

function load_dental_billing() {
    var table = $('#dental_billing_table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: "/appointment-invoice-items/" + _appointment_id,
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
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'service', name: 'service'},
            {data: 'tooth_no', name: 'tooth_no'},
            {data: 'amount', name: 'amount'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

// 旧的「开单弹窗」路径（AddInvoice / save_invoice / #addInvoiceItem → POST /invoices）
// 已删除：诊疗页改用与患者页共用的划价面板（billing.partials.charge_panel +
// BillingModule → POST /billing/create），两条路合成一条。
// 预约页与今日工作页各有自己的内联实现，不依赖本文件。



function editItem(id) {
    $('.loading').show();
    $.ajax({
        type: 'get',
        url: "/invoice-items/" + id + "/edit",
        success: function (data) {
            $('#invoice_item_id').val(id);
            $('[name="amount"]').val(data.amount);
            $('[name="tooth_no"]').val(data.tooth_no);

            let service_data = {
                id: data.medical_service_id,
                text: data.name
            };
            let newOption2 = new Option(service_data.text, service_data.id, true, true);
            $('#medical_service_id').append(newOption2).trigger('change');

            $('.loading').hide();
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#invoice-modal').modal('show');
        },
        error: function (request, status, error) {
            $('.loading').hide();
        }
    });
}

//filter Procedures
$('#service').select2({
    placeholder: LanguageManager.trans('common.select_procedure'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-medical-service',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
}).on("select2:select", function (e) {
    let price = e.params.data.price;
    if (price != "" || price != 0) {
        $('#procedure_price').val(price);
    } else {
        $('#procedure_price').val('');
    }

});
$('#medical_service_id').select2({
    placeholder: LanguageManager.trans('common.select_procedure'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-medical-service',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
});


function save_invoice_update() {
    $('.loading').show();

    $('#btnSave').attr('disabled', true);
    $('#btnSave').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#invoice-form').serialize(),
        url: "/invoice-items/" + $('#invoice_item_id').val(),
        success: function (data) {
            $('#invoice-modal').modal('hide');
            if (data.status) {
                alert_dental_billing(data.message, "success");
            } else {
                alert_dental_billing(data.message, "danger");
            }
            $('.loading').hide();
        },
        error: function (request, status, error) {
            $('.loading').hide();
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteItem(id) {
    swal({
            title: LanguageManager.trans('medical_treatment.are_you_sure'),
            text: LanguageManager.trans('medical_treatment.cannot_recover_invoice_item'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('medical_treatment.yes_delete_it'),
            closeOnConfirm: false
        },
        function () {

            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $('.loading').show();
            $.ajax({
                type: 'delete',
                data: {
                    _token: CSRF_TOKEN
                },
                url: "/invoice-items/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_dental_billing(data.message, "success");
                    } else {
                        alert_dental_billing(data.message, "danger");
                    }
                    $('.loading').hide();
                },
                error: function (request, status, error) {
                    $('.loading').hide();

                }
            });

        });

}

function alert_dental_billing(message, status) {
    swal(LanguageManager.trans('medical_treatment.alert'), message, status);

    let oTable = $('#dental_billing_table').dataTable();
    oTable.fnDraw(true);
}

