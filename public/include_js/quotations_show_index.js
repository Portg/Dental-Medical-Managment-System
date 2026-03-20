$(function () {
    var quotation_id = $('#global_quotation_id').val();

    var table = $('#quotation-items-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: "/quotation-items/" + quotation_id,
            data: function (d) {
            }
        },
        dom: 'Bfrtip',
        buttons: {
            buttons: []
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'service', name: 'service'},
            {data: 'tooth_no', name: 'tooth_no'},
            {data: 'qty', name: 'qty'},
            {data: 'price', name: 'price'},
            {data: 'total_amount', name: 'total_amount'},
            {data: 'added_by', name: 'added_by'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });

    $('#medical_service_id').select2({
        language: window.QuotationShowConfig.locale,
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
        var price = e.params.data.price;
        if (price != "" || price != 0) {
            $('#procedure_price').val(price);
            $('#procedure_qty').val(1);
            var amount = ($('#procedure_price').val().replace(/,/g, "")) * $('#procedure_qty').val();
            $('#total_amount').val(structureMoney("" + amount));
        } else {
            $('#procedure_price').val('');
            $('#procedure_qty').val('');
        }
    });

    $('#procedure_qty').on('keyup change', function () {
        if ($(this).val() && $('#procedure_price').val()) {
            $('#total_amount').val(structureMoney("" + $(this).val() * ($('#procedure_price').val().replace(/,/g, ""))))
        } else if (!$(this).val()) {
            $('#total_amount').val("")
        }
    });

    $('#procedure_price').on('keyup change', function () {
        if ($(this).val() && $('#procedure_qty').val()) {
            $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#procedure_qty').val()))
        } else if (!$(this).val()) {
            $('#total_amount').val("")
        }
    });
});

function createRecord() {
    $("#quotation-form")[0].reset();
    $('#btn-save').attr('disabled', false);
    var id = $('#global_quotation_id').val();
    $('#quotation_id').val(id);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#quotation-modal').modal('show');
}

function editItem(id) {
    $('.loading').show();
    $("#quotation-form")[0].reset();
    $('#btn-save').attr('disabled', false);
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: "/quotation-items/" + id + "/edit",
        success: function (data) {
            $.LoadingOverlay("hide");
            $('#id').val(id);
            $('[name="qty"]').val(data.qty);
            $('[name="price"]').val(data.price);
            $('[name="total_amount"]').val(data.price * data.qty);
            $('[name="tooth_no"]').val(data.tooth_no);

            var service_data = {
                id: data.medical_service_id,
                text: data.name
            };
            var newOption2 = new Option(service_data.text, service_data.id, true, true);
            $('#medical_service_id').append(newOption2).trigger('change');

            $('.loading').hide();
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#quotation-modal').modal('show');
        },
        error: function (request, status, error) {
            $('.loading').hide();
        }
    });
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
    $('.loading').show();
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'post',
        data: $('#quotation-form').serialize(),
        url: "/quotation-items/" + $('#id').val(),
        success: function (data) {
            $('#quotation-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $('.loading').hide();
        },
        error: function (request, status, error) {
            $('.loading').hide();
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_changes'));
            $('#quotation-modal').modal('show');
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#quotation-form').serialize(),
        url: "/quotation-items/" + $('#id').val(),
        success: function (data) {
            $('#quotation-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deleteItem(id) {
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('quotations.delete_item_confirm_message'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
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
                url: "/quotation-items/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $('.loading').hide();
                },
                error: function (request, status, error) {
                    $('.loading').hide();
                }
            });
        });
}

function structureMoney(value) {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function alert_dialog(message, status) {
    swal(LanguageManager.trans('common.alert'), message, status);

    if (status) {
        var oTable = $('#quotation-items-table').dataTable();
        oTable.fnDraw(false);
    }
}
