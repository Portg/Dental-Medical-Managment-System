let drugs_ary = [];

function mt(key, fallback) {
    if (typeof LanguageManager !== 'undefined' && LanguageManager.trans) {
        var v = LanguageManager.trans('medical_treatment.' + key);
        if (v && v !== 'medical_treatment.' + key) return v;
    }
    if (typeof LanguageManager !== 'undefined' && LanguageManager.trans) {
        var c = LanguageManager.trans('common.' + key);
        if (c && c !== 'common.' + key) return c;
    }
    return fallback || key;
}

$("#prescriptions_tab_link").on("click", function () {
    load_prescriptions();
});

function load_prescriptions() {
    let appointment_id = $('#global_appointment_id').val();
    var table = $('#prescriptions_table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            // appointment-scoped list (NOT /prescriptions/{id} which is show/detail)
            url: "/prescriptions/appointment/" + appointment_id,
            data: function (d) {
            }
        },
        dom: 'Bfrtip',
        buttons: {
            buttons: []
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'drug', name: 'drug'},
            {data: 'qty', name: 'qty'},
            {data: 'directions', name: 'directions'},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}


function AddPrescription(id) {
    $("#prescription-form")[0].reset();
    $('#prescription_id').val('');
    $('#prescription-modal #btn-save').attr('disabled', false);
    $('#prescription-modal #btn-save').text(mt('save_prescription', 'Save Prescription'));

    $('#prescription_appointment_id').val(id);
    $('#prescription-modal').modal('show');
}

$(document).ready(function () {
    $.ajax({
        type: 'get',
        url: "/filter-drugs",
        success: function (data) {
            drugs_ary = JSON.parse(data);
        }
    }).done(function () {
        $("#drug_name").typeahead({
            source: drugs_ary,
            minLength: 1
        });
    });
});


$(document).on('click', '.remove-tr', function () {
    $(this).parents('tr').remove();
});


let i = 0;
$("#add").click(function () {
    ++i;

    $("#prescriptionsTable").append(
        '<tr>' +
        '<td><input type="text" id="drug_name' + i + '" name="addmore[' + i + '][drug]" placeholder="' +
            mt('enter_drug', 'Enter drug') + '" class="form-control"/></td>' +
        '<td><input type="text" name="addmore[' + i + '][qty]" placeholder="' +
            mt('enter_qty_unit', 'Enter ml/mg') + '" class="form-control"/></td>' +
        '<td><textarea name="addmore[' + i + '][directions]" class="form-control"></textarea></td>' +
        '<td><button type="button" class="btn btn-danger remove-tr">' +
            mt('remove', 'Remove') + '</button></td>' +
        '</tr>');
    $("#drug_name" + i).typeahead({
        source: drugs_ary,
        minLength: 1
    });
});


function save_prescription() {
    $('.loading').show();
    $('#prescription-modal #btn-save').attr('disabled', true);
    $('#prescription-modal #btn-save').text(mt('processing', 'Processing...'));
    $.ajax({
        type: 'POST',
        data: $('#prescription-form').serialize(),
        url: "/prescriptions",
        success: function (data) {
            $('#prescription-modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                alert_prescriptions(data.message, "success");
            } else {
                alert_prescriptions(data.message, "danger");
            }
        },
        error: function (request) {
            $('.loading').hide();
            $('#prescription-modal #btn-save').attr('disabled', false);
            $('#prescription-modal #btn-save').text(mt('save_prescription', 'Save Prescription'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editPrescription(id) {
    $('.loading').show();
    $("#edit-prescription-form")[0].reset();
    $('#prescription_id').val('');
    $('#edit-prescription-modal #btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "/prescriptions/" + id + "/edit",
        success: function (data) {
            $('#prescription_id').val(id);

            $('[name="drug"]').val(data.drug);
            $('[name="qty"]').val(data.qty);
            $('[name="directions"]').val(data.directions);

            $('.loading').hide();
            $('#edit-prescription-modal #btn-save').text(mt('update_record', 'Update Record'));
            $('#edit-prescription-modal').modal('show');
        },
        error: function () {
            $('.loading').hide();
        }
    });
}

function update_prescription_record() {
    $('.loading').show();
    $('#edit-prescription-modal #btn-save').attr('disabled', true);
    $('#edit-prescription-modal #btn-save').text(mt('updating', 'Updating...'));
    $.ajax({
        type: 'PUT',
        data: $('#edit-prescription-form').serialize(),
        url: "/prescriptions/" + $('#prescription_id').val(),
        success: function (data) {
            $('#edit-prescription-modal').modal('hide');
            if (data.status) {
                alert_prescriptions(data.message, "success");
            } else {
                alert_prescriptions(data.message, "danger");
            }
            $('.loading').hide();
        },
        error: function (request) {
            $('.loading').hide();
            $('#edit-prescription-modal #btn-save').attr('disabled', false);
            $('#edit-prescription-modal #btn-save').text(mt('update_record', 'Update Record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deletePrescription(id) {
    swal({
            title: mt('are_you_sure', 'Are you sure?'),
            text: mt('cannot_recover_prescription', 'You will not be able to recover this prescription!'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: mt('yes_delete_it', 'Yes, delete it!'),
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
                url: "/prescriptions/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_prescriptions(data.message, "success");
                    } else {
                        alert_prescriptions(data.message, "danger");
                    }
                    $('.loading').hide();
                },
                error: function () {
                    $('.loading').hide();
                }
            });
        });
}


function alert_prescriptions(message, status) {
    swal(mt('alert', 'Alert!'), message, status);

    let oTable = $('#prescriptions_table').dataTable();
    oTable.fnDraw(true);
}
