@extends('layouts.list-page')

@section('page_title', __('roles.title'))

@section('table_id', 'roles_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.date') }}</th>
    <th>{{ __('roles.name') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('roles.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'roles': @json(__('roles')),
            'common': @json(__('common'))
        });

        dataTable = $('#roles_table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/roles') }}",
                data: function (d) {
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true, width: '50px'},
                {data: 'created_at', name: 'created_at', width: '160px'},
                {data: 'name', name: 'name'},
                {data: 'action', name: 'action', orderable: false, searchable: false, width: '90px'}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#roles-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#roles-modal').modal('show');
    }

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
        $('#btn-save').text('{{ __("common.processing") }}');
        $.ajax({
            type: 'POST',
            data: $('#roles-form').serialize(),
            url: "{{ url('/roles') }}",
            success: function (data) {
                $('#roles-modal').modal('hide');
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

    function editRecord(id) {
        $.LoadingOverlay("show");
        $("#roles-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $.ajax({
            type: 'get',
            url: "{{ url('/roles') }}/" + id + "/edit",
            success: function (data) {
                console.log(data);
                $('#id').val(id);
                $('[name="name"]').val(data.name);
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#roles-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.updating") }}');
        $.ajax({
            type: 'PUT',
            data: $('#roles-form').serialize(),
            url: "{{ url('/roles') }}/" + $('#id').val(),
            success: function (data) {
                $('#roles-modal').modal('hide');
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
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

    function deleteRecord(id) {
        swal({
                title: "{{ __('common.are_you_sure') }}",
                text: "{{ __('roles.delete_confirm_message') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "{{ __('common.yes_delete_it') }}",
                cancelButtonText: "{{ __('common.cancel') }}",
                closeOnConfirm: false
            },
            function () {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'delete',
                    data: {_token: CSRF_TOKEN},
                    url: "{{ url('/roles') }}/" + id,
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
</script>
@endsection
