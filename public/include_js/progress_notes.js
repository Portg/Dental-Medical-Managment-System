$(document).ready(function () {
    $('a[href="#progress_notes_tab"]').on('shown.bs.tab', function() {
        loadProgressNotes();
    });

    $('[data-toggle="tooltip"]').tooltip();

    // Initialize progress note template dropdown
    initProgressNoteTemplateDropdown();
});

function initProgressNoteTemplateDropdown() {
    // Load templates for progress notes dropdown
    $.ajax({
        url: '/medical-templates-search',
        type: 'GET',
        data: { type: 'progress_note' },
        success: function(response) {
            if (response.status && response.data && response.data.length > 0) {
                var $select = $('#progress_note_template_select');
                $select.empty();
                $select.append('<option value="">' + (LanguageManager.trans('templates.select_template') || 'Select a template...') + '</option>');

                response.data.forEach(function(template) {
                    var categoryLabel = template.category === 'system' ? '[系统]' :
                                        template.category === 'personal' ? '[个人]' : '[科室]';
                    $select.append('<option value="' + template.id + '" data-content=\'' +
                        JSON.stringify(template.content) + '\'>' + categoryLabel + ' ' + template.name + '</option>');
                });

                // Handle template selection
                $select.off('change').on('change', function() {
                    var templateId = $(this).val();
                    if (!templateId) return;

                    var content = $(this).find('option:selected').data('content');
                    if (content) {
                        applyTemplateToSOAP(content);
                        // Reset dropdown
                        $(this).val('');
                        // Increment usage
                        $.post('/medical-templates/' + templateId + '/increment-usage');
                    }
                });
            }
        }
    });
}

function applyTemplateToSOAP(content) {
    try {
        var parsed = typeof content === 'string' ? JSON.parse(content) : content;
        if (parsed.subjective) $('#subjective').val(parsed.subjective);
        if (parsed.objective) $('#objective').val(parsed.objective);
        if (parsed.assessment) $('#assessment').val(parsed.assessment);
        if (parsed.plan) $('#plan').val(parsed.plan);
    } catch(e) {
        // If content is plain text, put it in subjective
        $('#subjective').val(content);
    }
}

function loadProgressNotes() {
    var url = '/case-progress-notes/' + global_case_id;

    $('#progress_notes_table').DataTable({
        language: LanguageManager.getDataTableLang(),
        destroy: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            data: function (d) {}
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'note_date', name: 'note_date'},
            {data: 'noteTypeBadge', name: 'noteTypeBadge', orderable: false, searchable: false},
            {data: 'added_by', name: 'added_by'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ]
    });
}

function addProgressNote() {
    $('#progress_note_form')[0].reset();
    $('#progress_note_id').val('');
    $('#note_medical_case_id').val(global_case_id);
    $('#note_patient_id').val(global_patient_id);

    // Set current datetime
    var now = new Date();
    var datetime = now.toISOString().slice(0, 16);
    $('#note_date').val(datetime);

    $('#progress_note_modal_title').text(LanguageManager.trans('medical_cases.add_progress_note'));
    $('#btn_save_progress_note').text(LanguageManager.trans('common.save'));
    $('#progress_note_modal').modal('show');
}

function viewProgressNote(id) {
    $('.loading').show();

    $.ajax({
        type: 'GET',
        url: '/progress-notes/' + id,
        success: function(data) {
            $('#view_note_date').text(data.note_date);
            $('#view_note_type').text(data.note_type);
            $('#view_subjective').text(data.subjective || '-');
            $('#view_objective').text(data.objective || '-');
            $('#view_assessment').text(data.assessment || '-');
            $('#view_plan').text(data.plan || '-');

            $('.loading').hide();
            $('#view_progress_note_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function editProgressNote(id) {
    $('.loading').show();
    $('#progress_note_form')[0].reset();

    $.ajax({
        type: 'GET',
        url: '/progress-notes/' + id + '/edit',
        success: function(data) {
            $('#progress_note_id').val(id);
            $('#note_medical_case_id').val(data.medical_case_id);
            $('#note_patient_id').val(data.patient_id);

            // Format datetime for input
            if (data.note_date) {
                var noteDate = new Date(data.note_date);
                var datetime = noteDate.toISOString().slice(0, 16);
                $('#note_date').val(datetime);
            }

            $('#note_type').val(data.note_type);
            $('#subjective').val(data.subjective);
            $('#objective').val(data.objective);
            $('#assessment').val(data.assessment);
            $('#plan').val(data.plan);

            $('#progress_note_modal_title').text(LanguageManager.trans('medical_cases.edit_progress_note'));
            $('#btn_save_progress_note').text(LanguageManager.trans('common.update'));
            $('.loading').hide();
            $('#progress_note_modal').modal('show');
        },
        error: function() {
            $('.loading').hide();
        }
    });
}

function saveProgressNote() {
    var id = $('#progress_note_id').val();
    if (id === '') {
        createProgressNote();
    } else {
        updateProgressNote(id);
    }
}

function createProgressNote() {
    $('.loading').show();
    $('#btn_save_progress_note').attr('disabled', true);

    $.ajax({
        type: 'POST',
        url: '/progress-notes',
        data: $('#progress_note_form').serialize(),
        success: function(data) {
            $('#progress_note_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#progress_notes_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_progress_note').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_progress_note').attr('disabled', false);
            handleProgressNoteErrors(request);
        }
    });
}

function updateProgressNote(id) {
    $('.loading').show();
    $('#btn_save_progress_note').attr('disabled', true);

    $.ajax({
        type: 'PUT',
        url: '/progress-notes/' + id,
        data: $('#progress_note_form').serialize(),
        success: function(data) {
            $('#progress_note_modal').modal('hide');
            $('.loading').hide();
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                $('#progress_notes_table').DataTable().ajax.reload();
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
            $('#btn_save_progress_note').attr('disabled', false);
        },
        error: function(request) {
            $('.loading').hide();
            $('#btn_save_progress_note').attr('disabled', false);
            handleProgressNoteErrors(request);
        }
    });
}

function deleteProgressNote(id) {
    swal({
        title: LanguageManager.trans('medical_cases.confirm_delete'),
        text: LanguageManager.trans('medical_cases.confirm_delete_progress_note'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function() {
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        $('.loading').show();
        $.ajax({
            type: 'DELETE',
            url: '/progress-notes/' + id,
            data: { _token: CSRF_TOKEN },
            success: function(data) {
                $('.loading').hide();
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    $('#progress_notes_table').DataTable().ajax.reload();
                } else {
                    swal(LanguageManager.trans('common.error'), data.message, "error");
                }
            },
            error: function() {
                $('.loading').hide();
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('messages.error_occurred'), "error");
            }
        });
    });
}

function handleProgressNoteErrors(request) {
    if (request.responseJSON && request.responseJSON.errors) {
        var errors = request.responseJSON.errors;
        var errorMsg = '';
        $.each(errors, function(key, value) {
            errorMsg += value + '\n';
        });
        swal(LanguageManager.trans('common.validation_error'), errorMsg, "error");
    }
}
