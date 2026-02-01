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
                    <span class="caption-subject">{{ __('templates.quick_phrases') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createPhrase()">{{ __('common.add_new') }}</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_scope" class="form-control">
                                <option value="">{{ __('templates.all_scopes') }}</option>
                                <option value="system">{{ __('templates.system') }}</option>
                                <option value="personal">{{ __('templates.personal') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_category" class="form-control">
                                <option value="">{{ __('templates.all_categories') }}</option>
                                <option value="examination">{{ __('templates.examination') }}</option>
                                <option value="diagnosis">{{ __('templates.diagnosis') }}</option>
                                <option value="treatment">{{ __('templates.treatment') }}</option>
                                <option value="other">{{ __('templates.other') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="phrases_table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('templates.shortcut') }}</th>
                        <th>{{ __('templates.phrase') }}</th>
                        <th>{{ __('templates.category') }}</th>
                        <th>{{ __('templates.scope') }}</th>
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
@include('quick_phrases.create')
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

        table = $('#phrases_table').DataTable({
            destroy: true,
            processing: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/quick-phrases/') }}",
                data: function (d) {
                    d.scope = $('#filter_scope').val();
                    d.category = $('#filter_category').val();
                }
            },
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

        $('#filter_scope, #filter_category').change(function() {
            table.ajax.reload();
        });
    });

    function createPhrase() {
        $("#phrase-form")[0].reset();
        $('#phrase_id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#phrase-modal .modal-title').text('{{ __("templates.create_phrase") }}');
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
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'POST',
            data: $('#phrase-form').serialize(),
            url: "{{ url('/quick-phrases') }}",
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
                $('#btn-save').text('{{ __("common.save_record") }}');
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
            url: "{{ url('/quick-phrases') }}/" + id,
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
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#phrase-modal .modal-title').text('{{ __("templates.edit_phrase") }}');
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
        $('#btn-save').text('{{ __("common.updating") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#phrase-form').serialize(),
            url: "{{ url('/quick-phrases') }}/" + $('#phrase_id').val(),
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
                $('#btn-save').text('{{ __("common.update_record") }}');
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
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('templates.delete_phrase_confirm') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            closeOnConfirm: false,
            cancelButtonText: "{{ __('common.cancel') }}"
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "{{ url('/quick-phrases') }}/" + id,
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
