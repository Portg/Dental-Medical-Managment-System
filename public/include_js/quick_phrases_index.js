$(function () {
    dataTable = $('#phrases_table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.QuickPhrasesConfig.listUrl,
            data: function (d) {
                d.scope = $('#filter_scope').val();
                d.category = $('#filter_category').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'shortcut', name: 'shortcut'},
            {data: 'phrase', name: 'phrase'},
            {data: 'category_label', name: 'category_label'},
            {data: 'scope_label', name: 'scope_label'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();

    $('#filter_scope, #filter_category').change(function() {
        doSearch();
    });
});

function createPhrase() {
    $("#phrase-form")[0].reset();
    $('#phrase_id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#phrase-modal .modal-title').text(LanguageManager.trans('templates.create_phrase'));
    $('#phrase-modal').modal('show');
}

function save_phrase() {
    var id = $('#phrase_id').val();
    if (id == "") {
        save_new_phrase();
    } else {
        update_phrase();
    }
}

function save_new_phrase() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'POST',
        data: $('#phrase-form').serialize(),
        url: window.QuickPhrasesConfig.baseUrl,
        success: function (data) {
            $('#phrase-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editPhrase(id) {
    $.LoadingOverlay("show");
    $("#phrase-form")[0].reset();
    $('#phrase_id').val('');
    $('#btn-save').attr('disabled', false);

    $.ajax({
        type: 'get',
        url: window.QuickPhrasesConfig.baseUrl + '/' + id,
        success: function (response) {
            if (response.status) {
                var data = response.data;
                $('#phrase_id').val(data.id);
                $('[name="shortcut"]').val(data.shortcut);
                $('[name="phrase"]').val(data.phrase);
                $('[name="category"]').val(data.category);
                $('[name="scope"]').val(data.scope);
                $('[name="is_active"]').prop('checked', data.is_active);
            }
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#phrase-modal .modal-title').text(LanguageManager.trans('templates.edit_phrase'));
            $('#phrase-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_phrase() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'PUT',
        data: $('#phrase-form').serialize(),
        url: window.QuickPhrasesConfig.baseUrl + '/' + $('#phrase_id').val(),
        success: function (data) {
            $('#phrase-modal').modal('hide');
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
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function deletePhrase(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('templates.delete_phrase_confirm'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        closeOnConfirm: false,
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: window.QuickPhrasesConfig.baseUrl + '/' + id,
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
