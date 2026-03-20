$(function () {
    LanguageManager.loadAllFromPHP(window.SalaryAdvancesConfig.translations);

    dataTable = $('#salary-advances-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.SalaryAdvancesConfig.indexUrl,
            data: function (d) {
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'employee', name: 'employee'},
            {data: 'payment_classification', name: 'payment_classification'},
            {data: 'advance_month', name: 'advance_month'},
            {data: 'amount', name: 'amount'},
            {data: 'payment_method', name: 'payment_method'},
            {data: 'payment_date', name: 'payment_date'},
            {data: 'addedBy', name: 'addedBy'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});

function createRecord() {
    $("#scale-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('salary_advances.save_changes'));
    $('#scale-modal').modal('show');
}

// Select2 employee search
$('#employee').select2({
    language: window.SalaryAdvancesConfig.locale,
    placeholder: LanguageManager.trans('salary_advances.choose_employee'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-employee',
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

function save_data() {
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
    $('#btn-save').text(LanguageManager.trans('salary_advances.processing'));
    $.ajax({
        type: 'POST',
        data: $('#scale-form').serialize(),
        url: "/salary-advances",
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
            $('#btn-save').text(LanguageManager.trans('salary_advances.save_changes'));
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
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "salary-advances/" + id + "/edit",
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
            $('#btn-save').text(LanguageManager.trans('salary_advances.update_record'));
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
    $('#btn-save').text(LanguageManager.trans('salary_advances.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#scale-form').serialize(),
        url: "/salary-advances/" + $('#id').val(),
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
            title: LanguageManager.trans('salary_advances.are_you_sure'),
            text: LanguageManager.trans('salary_advances.cannot_recover_advance'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('salary_advances.yes_delete'),
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
                url: "/salary-advances/" + id,
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
