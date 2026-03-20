// ==========================================================================
// Claim Rates Index Page
// ==========================================================================

$(function () {
    dataTable = $('#claim-rates-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.ClaimRatesConfig.indexUrl,
            data: function (d) {
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'created_at', name: 'created_at'},
            {data: 'surname', name: 'surname'},
            {data: 'othername', name: 'othername'},
            {data: 'cash_rate', name: 'cash_rate'},
            {data: 'insurance_rate', name: 'insurance_rate'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();

    // Select2 doctor search
    $('#doctor').select2({
        language: window.ClaimRatesConfig.locale,
        placeholder: LanguageManager.trans('common.choose_doctor'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-doctor',
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
});

function createRecord() {
    $("#rate-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $('#rate-modal').modal('show');
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
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#rate-form').serialize(),
        url: window.ClaimRatesConfig.baseUrl,
        success: function (data) {
            $('#rate-modal').modal('hide');
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
            $('#rate-modal').modal('show');

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
    $("#rate-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: window.ClaimRatesConfig.baseUrl + '/' + id + '/edit',
        success: function (data) {
            $('#id').val(id);
            $('[name="insurance_rate"]').val(data.insurance_rate);
            $('[name="cash_rate"]').val(data.cash_rate);
            let doctor_data = {
                id: data.doctor_id,
                text: LanguageManager.joinName(data.surname, data.othername)
            };
            let newOption = new Option(doctor_data.text, doctor_data.id, true, true);
            $('#doctor').append(newOption).trigger('change');
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#rate-modal').modal('show');
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
        data: $('#rate-form').serialize(),
        url: window.ClaimRatesConfig.baseUrl + '/' + $('#id').val(),
        success: function (data) {
            $('#rate-modal').modal('hide');
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
            text: LanguageManager.trans('claim_rates.delete_confirm_message'),
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
                url: window.ClaimRatesConfig.baseUrl + '/' + id,
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

function newClaim(doctor_id, othername) {
    $("#new-claim-form")[0].reset();
    $('.renew_title').text(othername + " " + LanguageManager.trans('claim_rates.new_claim_rate'));
    $('#doctor_id').val(doctor_id);
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_changes'));
    $('#new-claim-modal').modal('show');
}

function save_new_rate() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#new-claim-form').serialize(),
        url: window.ClaimRatesConfig.baseUrl,
        success: function (data) {
            $('#new-claim-modal').modal('hide');
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
            $('#new-claim-modal').modal('show');

            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}
