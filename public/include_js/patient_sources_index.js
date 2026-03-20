// ==========================================================================
// Patient Sources Index Page
// ==========================================================================

$(function() {
    // Initialize DataTable
    dataTable = $('#sources-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.PatientSourcesConfig.indexUrl,
            data: function(d) {
                d.quick_search = $('#quickSearch').val();
                d.status = $('#filter_status').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'name', name: 'name'},
            {data: 'code', name: 'code'},
            {data: 'patients_count', name: 'patients_count'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Setup empty state handler
    setupEmptyStateHandler();

    // Quick search with debounce
    $('#quickSearch').on('keyup', debounce(function() {
        dataTable.draw(true);
    }, 300));

    // Status filter auto-apply
    $('#filter_status').on('change', function() {
        dataTable.draw(true);
    });
});

// ==========================================================================
// Override Base Functions
// ==========================================================================

function doSearch() {
    if (dataTable) {
        dataTable.draw(true);
    }
}

function clearFilters() {
    $('#quickSearch').val('');
    $('#filter_status').val('');
    doSearch();
}

function createRecord() {
    $("#source-form")[0].reset();
    $('#source_id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#source-modal .modal-title').text(LanguageManager.trans('patient_tags.create_source'));
    $('#source-modal').modal('show');
}

// Alias for controller compatibility
function createSource() {
    createRecord();
}

function editRecord(id) {
    $.LoadingOverlay("show");
    $("#source-form")[0].reset();
    $('#source_id').val('');
    $('#btn-save').attr('disabled', false);

    $.ajax({
        type: 'get',
        url: window.PatientSourcesConfig.baseUrl + '/' + id,
        success: function(response) {
            if (response.status) {
                var data = response.data;
                $('#source_id').val(data.id);
                $('[name="name"]').val(data.name);
                $('[name="code"]').val(data.code);
                $('[name="description"]').val(data.description);
                $('[name="is_active"]').prop('checked', data.is_active);
            }
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#source-modal .modal-title').text(LanguageManager.trans('patient_tags.edit_source'));
            $('#source-modal').modal('show');
        },
        error: function() {
            $.LoadingOverlay("hide");
        }
    });
}

// Alias for controller compatibility
function editSource(id) {
    editRecord(id);
}

function deleteRecord(id) {
    var sweetAlertLang = LanguageManager.getSweetAlertLang();
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('patient_tags.delete_source_confirm'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        cancelButtonText: sweetAlertLang.cancel,
        closeOnConfirm: false
    }, function() {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: window.PatientSourcesConfig.baseUrl + '/' + id,
            success: function(data) {
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
            },
            error: function() {
                $.LoadingOverlay("hide");
            }
        });
    });
}

// Alias for controller compatibility
function deleteSource(id) {
    deleteRecord(id);
}

// ==========================================================================
// Form CRUD Functions
// ==========================================================================

function save_source() {
    var id = $('#source_id').val();
    if (id === "") {
        save_new_source();
    } else {
        update_source();
    }
}

function save_new_source() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'POST',
        data: $('#source-form').serialize(),
        url: window.PatientSourcesConfig.baseUrl,
        success: function(data) {
            $('#source-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function(request) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function(key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function update_source() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $('.alert-danger').hide().empty();

    $.ajax({
        type: 'PUT',
        data: $('#source-form').serialize(),
        url: window.PatientSourcesConfig.baseUrl + '/' + $('#source_id').val(),
        success: function(data) {
            $('#source-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function(request) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            var json = $.parseJSON(request.responseText);
            $.each(json.errors, function(key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}
