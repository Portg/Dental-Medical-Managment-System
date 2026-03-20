$(function () {
    dataTable = $('#payslips-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.PayslipsConfig.ajaxUrl,
            data: function (d) {
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'employee', name: 'employee'},
            {data: 'payslip_month', name: 'payslip_month'},
            {data: 'basic_salary', name: 'basic_salary'},
            {data: 'total_allowances', name: 'total_allowances'},
            {data: 'total_deductions', name: 'total_deductions'},
            {data: 'total_advances', name: 'total_advances'},
            {data: 'due_balance', name: 'due_balance'},
            {data: 'addedBy', name: 'addedBy'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function createRecord() {
    $("#scale-form")[0].reset();
    $('#id').val(''); ///always reset hidden form fields
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $('#scale-modal').modal('show');
}

$(document).on('click', '.remove-tr', function () {
    $(this).parents('tr').remove();
});


let i = 0;
$("#add_allowance").click(function () {
    ++i;

    $("#AllowancesTable").append(
        '<tr>' +
        '<td>  <select class="form-control" name="addAllowance[' + i + '][allowance]">\n' +
        '                                        <option value="House Rent Allowance">' + LanguageManager.trans('allowances.house_rent') + '</option>\n' +
        '                                        <option value="Medical Allowance">' + LanguageManager.trans('allowances.medical') + '</option>\n' +
        '                                        <option value="Bonus">' + LanguageManager.trans('allowances.bonus') + '</option>\n' +
        '                                        <option value="Dearness Allowance">' + LanguageManager.trans('allowances.dearness') + '</option>\n' +
        '                                        <option value="Travelling Allowance">' + LanguageManager.trans('allowances.travelling') + '</option>\n' +
        '                                        <option value="Overtime Allowance">' + LanguageManager.trans('allowances.overtime') + '</option>\n' +
        '                                    </select></td>' +
        '<td> <input type="number"  name="addAllowance[' + i + '][allowance_amount]" placeholder="' + LanguageManager.trans('common.enter_amount') + '" class="form-control"/></td>' +
        '<td><button type="button" class="btn btn-danger remove-tr">' + LanguageManager.trans('common.remove') + '</button></td>' +
        '</tr>');
});


let x = 0;
$("#add_deduction").click(function () {
    ++x;
    $("#DeductionsTable").append(
        '<tr>' +
        '<td>  <select class="form-control" name="addDeduction[' + x + '][deduction]">\n' +
        ' <option value="Loan">' + LanguageManager.trans('deductions.loan') + '</option>\n' +
        ' <option value="Tax">' + LanguageManager.trans('deductions.tax') + '</option>' +
        '</select></td>' +
        '<td> <input type="number"  name="addDeduction[' + x + '][deduction_amount]" placeholder="' + LanguageManager.trans('common.enter_amount') + '" class="form-control"/></td>' +
        '<td><button type="button" class="btn btn-danger remove-tr">' + LanguageManager.trans('common.remove') + '</button></td>' +
        '</tr>');
});


//filter employee
$('#employee').select2({
    language: window.PayslipsConfig.locale,
    placeholder: LanguageManager.trans('payslips.choose_employee'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-employee',
        dataType: 'json',
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

$(document).ready(function () {
    //first hide the allowances and deductions fields
    $('#AllowancesTable').hide();
    $('#DeductionsTable').hide();
    //now first handle allowances
    $("input[type=radio][name=allowances_include]").on("change", function () {
        let action = $("input[type=radio][name=allowances_include]:checked").val();

        if (action == "No") {
            //now hide the view
            $('#AllowancesTable').hide();
        } else {
            //show allowances table fields
            $('#AllowancesTable').show();
        }
    });

    //handle deductions
    $("input[type=radio][name=deductions_include]").on("change", function () {
        let action = $("input[type=radio][name=deductions_include]:checked").val();

        if (action == "No") {
            //now hide the view
            $('#DeductionsTable').hide();
        } else {
            //show allowances table fields
            $('#DeductionsTable').show();
        }
    });


});


function save_data() {
    //check save method
    var id = $('#id').val();
    if (id == "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#scale-form').serialize(),
        url: "/payslips",
        success: function (data) {
            $('#scale-modal').modal('hide');
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
            $('#scale-modal').modal('show');

            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#scale-form")[0].reset();
    $('#id').val(''); ///always reset hidden form fields
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "payslips/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            $('[name="amount"]').val(data.advance_amount);
            $('[name="advance_month"]').val(data.advance_month);
            $('[name="payment_date"]').val(data.payment_date);
            let employee_data = {
                id: data.employee_id,
                text: LanguageManager.joinName(data.surname, data.othername)
            };
            let newOption = new Option(employee_data.text, employee_data.id, true, true);
            $('#employee').append(newOption).trigger('change');
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#scale-modal').modal('show');

        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#scale-form').serialize(),
        url: "/payslips/" + $('#id').val(),
        success: function (data) {
            $('#scale-modal').modal('hide');
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
            text: LanguageManager.trans('payslips.delete_confirm_message'),
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
                url: "/payslips/" + id,
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
