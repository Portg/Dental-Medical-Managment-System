$(function () {
    dataTable = $('#templates_table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.MedicalTemplatesConfig.baseUrl,
            data: function (d) {
                d.category = $('#filter_category').val();
                d.type = $('#filter_type').val();
            }
        },
        dom: 'rtip',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'name', name: 'name'},
            {data: 'code', name: 'code'},
            {data: 'category_label', name: 'category_label'},
            {data: 'type_label', name: 'type_label'},
            {data: 'usage_count', name: 'usage_count'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    setupEmptyStateHandler();

    $('#filter_category, #filter_type').change(function() {
        doSearch();
    });
});

function createTemplate() {
    clearTemplateForm();
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('#template-modal .modal-title').text(LanguageManager.trans('templates.create_template'));
    toggleContentFields();
    $('#template-modal').modal('show');
}

function save_template() {
    var id = $('#template_id').val();
    if (id == "") {
        save_new_template();
    } else {
        update_template();
    }
}

function getFormDataWithContent() {
    var formData = new FormData($('#template-form')[0]);
    // Add the structured content
    formData.append('content', getTemplateContent());
    return formData;
}

function save_new_template() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $('.alert-danger').hide().empty();

    var formData = $('#template-form').serializeArray();
    formData.push({name: 'content', value: getTemplateContent()});

    $.ajax({
        type: 'POST',
        data: $.param(formData),
        url: window.MedicalTemplatesConfig.baseUrl,
        success: function (data) {
            $('#template-modal').modal('hide');
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

function editTemplate(id) {
    $.LoadingOverlay("show");
    clearTemplateForm();
    $('#btn-save').attr('disabled', false);

    $.ajax({
        type: 'get',
        url: window.MedicalTemplatesConfig.baseUrl + '/' + id,
        success: function (response) {
            if (response.status) {
                var data = response.data;
                $('#template_id').val(data.id);
                $('[name="name"]').val(data.name);
                $('[name="code"]').val(data.code);
                $('[name="category"]').val(data.category);
                $('[name="type"]').val(data.type);
                $('#template_type').val(data.type);
                $('[name="department"]').val(data.department);
                $('[name="description"]').val(data.description);
                $('[name="is_active"]').prop('checked', data.is_active);

                // Set content based on type
                toggleContentFields();
                setTemplateContent(data.content, data.type);
            }
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
            $('#template-modal .modal-title').text(LanguageManager.trans('templates.edit_template'));
            $('#template-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function update_template() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $('.alert-danger').hide().empty();

    var formData = $('#template-form').serializeArray();
    formData.push({name: 'content', value: getTemplateContent()});

    $.ajax({
        type: 'PUT',
        data: $.param(formData),
        url: window.MedicalTemplatesConfig.baseUrl + '/' + $('#template_id').val(),
        success: function (data) {
            $('#template-modal').modal('hide');
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

function previewTemplate(id) {
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: window.MedicalTemplatesConfig.baseUrl + '/' + id,
        success: function (response) {
            if (response.status) {
                var data = response.data;
                var noContent = LanguageManager.trans('templates.no_content');

                $('#preview-name').text(data.name);
                $('#preview-code').text(data.code);

                // Display localized category and type labels
                var categoryLabels = {
                    'system': LanguageManager.trans('templates.system'),
                    'department': LanguageManager.trans('templates.department'),
                    'personal': LanguageManager.trans('templates.personal')
                };
                var typeLabels = {
                    'progress_note': LanguageManager.trans('templates.progress_note'),
                    'diagnosis': LanguageManager.trans('templates.diagnosis'),
                    'treatment_plan': LanguageManager.trans('templates.treatment_plan'),
                    'chief_complaint': LanguageManager.trans('templates.chief_complaint')
                };
                $('#preview-category').text(categoryLabels[data.category] || data.category);
                $('#preview-type').text(typeLabels[data.type] || data.type);
                $('#preview-description').text(data.description || '-');

                // Display content based on type
                if (data.type === 'progress_note') {
                    $('#preview-soap-content').show();
                    $('#preview-simple-content').hide();

                    try {
                        var content = typeof data.content === 'string' ? JSON.parse(data.content) : data.content;
                        $('#preview-subjective').html(content.subjective || '<em class="text-muted">' + noContent + '</em>');
                        $('#preview-objective').html(content.objective || '<em class="text-muted">' + noContent + '</em>');
                        $('#preview-assessment').html(content.assessment || '<em class="text-muted">' + noContent + '</em>');
                        $('#preview-plan').html(content.plan || '<em class="text-muted">' + noContent + '</em>');
                    } catch (e) {
                        // If not valid JSON, show as simple content
                        $('#preview-soap-content').hide();
                        $('#preview-simple-content').show();
                        $('#preview-content').text(data.content);
                    }
                } else {
                    $('#preview-soap-content').hide();
                    $('#preview-simple-content').show();
                    $('#preview-content').text(data.content);
                }
            }
            $.LoadingOverlay("hide");
            $('#preview-modal').modal('show');
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}

function deleteTemplate(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('templates.delete_confirm_message'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        closeOnConfirm: false
    }, function () {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'delete',
            data: { _token: CSRF_TOKEN },
            url: window.MedicalTemplatesConfig.baseUrl + '/' + id,
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
