@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('menu.settings') }} / {{ __('templates.medical_templates') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="#" onclick="createTemplate()">
                                    {{ __('common.add_new') }} <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_category" class="form-control">
                                <option value="">{{ __('templates.all_categories') }}</option>
                                <option value="system">{{ __('templates.system') }}</option>
                                <option value="department">{{ __('templates.department') }}</option>
                                <option value="personal">{{ __('templates.personal') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_type" class="form-control">
                                <option value="">{{ __('templates.all_types') }}</option>
                                <option value="progress_note">{{ __('templates.progress_note') }}</option>
                                <option value="diagnosis">{{ __('templates.diagnosis') }}</option>
                                <option value="treatment_plan">{{ __('templates.treatment_plan') }}</option>
                                <option value="chief_complaint">{{ __('templates.chief_complaint') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="templates_table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('templates.name') }}</th>
                        <th>{{ __('templates.code') }}</th>
                        <th>{{ __('templates.category') }}</th>
                        <th>{{ __('templates.type') }}</th>
                        <th>{{ __('templates.usage_count') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('medical_templates.create')
@include('medical_templates.preview')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    var table;

    $(function () {
        LanguageManager.loadAllFromPHP({
            'templates': @json(__('templates')),
            'common': @json(__('common'))
        });

        table = $('#templates_table').DataTable({
            destroy: true,
            processing: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/medical-templates/') }}",
                data: function (d) {
                    d.category = $('#filter_category').val();
                    d.type = $('#filter_type').val();
                }
            },
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

        $('#filter_category, #filter_type').change(function() {
            table.ajax.reload();
        });
    });

    function createTemplate() {
        clearTemplateForm();
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#template-modal .modal-title').text('{{ __("templates.create_template") }}');
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
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().empty();

        var formData = $('#template-form').serializeArray();
        formData.push({name: 'content', value: getTemplateContent()});

        $.ajax({
            type: 'POST',
            data: $.param(formData),
            url: "{{ url('/medical-templates') }}",
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
                $('#btn-save').text('{{ __("common.save_record") }}');
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
            url: "{{ url('/medical-templates') }}/" + id,
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
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#template-modal .modal-title').text('{{ __("templates.edit_template") }}');
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
        $('#btn-save').text('{{ __("common.updating") }}');
        $('.alert-danger').hide().empty();

        var formData = $('#template-form').serializeArray();
        formData.push({name: 'content', value: getTemplateContent()});

        $.ajax({
            type: 'PUT',
            data: $.param(formData),
            url: "{{ url('/medical-templates') }}/" + $('#template_id').val(),
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
                $('#btn-save').text('{{ __("common.update_record") }}');
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
            url: "{{ url('/medical-templates') }}/" + id,
            success: function (response) {
                if (response.status) {
                    var data = response.data;
                    var noContent = '{{ __("templates.no_content") }}';

                    $('#preview-name').text(data.name);
                    $('#preview-code').text(data.code);

                    // Display localized category and type labels
                    var categoryLabels = {
                        'system': '{{ __("templates.system") }}',
                        'department': '{{ __("templates.department") }}',
                        'personal': '{{ __("templates.personal") }}'
                    };
                    var typeLabels = {
                        'progress_note': '{{ __("templates.progress_note") }}',
                        'diagnosis': '{{ __("templates.diagnosis") }}',
                        'treatment_plan': '{{ __("templates.treatment_plan") }}',
                        'chief_complaint': '{{ __("templates.chief_complaint") }}'
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
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('templates.delete_confirm_message') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            closeOnConfirm: false
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "{{ url('/medical-templates') }}/" + id,
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

    function alert_dialog(message, status) {
        swal("{{ __('common.alert') }}", message, status);
        setTimeout(function () {
            location.reload();
        }, 1900);
    }
</script>
@endsection
