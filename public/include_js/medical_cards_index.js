$(function () {

    // 批量加载
    LanguageManager.loadAllFromPHP(window.MedicalCardsConfig.translations);

    dataTable = $('#sample_1').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.MedicalCardsConfig.medicalCardsUrl,
            data: function (d) {
                d.search = $('input[type="search"]').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'created_at', name: 'created_at'},
            {data: 'patient', name: 'patient'},
            {data: 'card_type', name: 'card_type'},
            {data: 'added_by', name: 'added_by'},
            {data: 'view_cards', name: 'view_cards'},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false},
            {"data": "checkbox", orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();
});


function createRecord() {
    $("#card-form")[0].reset();
    $('#card-modal').modal('show');
}


// filter patients
$('#patient').select2({
    language: window.MedicalCardsConfig.locale,
    placeholder: LanguageManager.trans('patient.choose_patient'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-patient',
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
    // check save method
    var id = $('#id').val();
    if (id === "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btnSave').attr('disabled', true);
    $('#btnSave').text(LanguageManager.trans('common.processing'));
    let form = $('#card-form')[0];
    let formData = new FormData(form);

    $.ajax({
        type: 'POST',
        url: window.MedicalCardsConfig.medicalCardsUrl,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: (data) => {
            $('#card-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
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

function editRecord(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: "medical-cards/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            $('[name="name"]').val(data.name);
            let patient_data = {
                id: data.patient_id,
                text: LanguageManager.joinName(data.surname, data.othername)
            };
            let newOption = new Option(patient_data.text, patient_data.id, true, true);
            $('#patient').append(newOption).trigger('change');

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#card-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_record() {
    $.LoadingOverlay("show");

    $('#btnSave').attr('disabled', true);
    $('#btnSave').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#category-form').serialize(),
        url: "/expense-categories/" + $('#id').val(),
        success: function (data) {
            $('#category-modal').modal('hide');
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
            text: LanguageManager.trans('medical_cards.cannot_recover_card'),
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
                url: "/medical-cards/" + id,
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


$("#uploadFile").change(function () {
    var total_file = document.getElementById("uploadFile").files.length;

    for (var i = 0; i < total_file; i++) {
        $('#image_preview').append("<img src='" + URL.createObjectURL(event.target.files[i]) + "'>");
    }
});


$(document).on('click', '#bulk_delete', function () {
    var id = [];
    if (confirm(LanguageManager.trans('common.are_you_sure'))) {
        $('.student_checkbox:checked').each(function () {
            id.push($(this).val());
        });
        if (id.length > 0) {
            $.ajax({
                url: window.MedicalCardsConfig.massRemoveUrl,
                method: "get",
                data: {id: id},
                success: function (data) {
                    alert(data);
                    $('#student_table').DataTable().ajax.reload();
                }
            });
        } else {
            alert(LanguageManager.trans('common.please_select_checkbox'));
        }
    }
});
