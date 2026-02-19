@extends('layouts.list-page')

@section('page_title', __('chairs.chairs_management'))
@section('table_id', 'chairs-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('chairs.chair_code') }}</th>
    <th>{{ __('chairs.chair_name') }}</th>
    <th>{{ __('chairs.branch') }}</th>
    <th>{{ __('chairs.status') }}</th>
    <th>{{ __('chairs.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('chairs.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'chairs': @json(__('chairs')),
            'common': @json(__('common'))
        });

        dataTable = $('#chairs-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/chairs') }}",
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'chair_code', name: 'chair_code'},
                {data: 'chair_name', name: 'chair_name'},
                {data: 'branch', name: 'branch'},
                {data: 'statusLabel', name: 'statusLabel'},
                {data: 'addedBy', name: 'addedBy'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#chair-form")[0].reset();
        $('#chair_id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_changes") }}');
        $('.alert-danger').hide();
        $('#chair-modal').modal('show');
    }

    function save_data() {
        var id = $('#chair_id').val();
        if (id === "") {
            save_new_record();
        } else {
            update_record();
        }
    }

    function save_new_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().find('ul').empty();

        $.ajax({
            type: 'POST',
            data: $('#chair-form').serialize(),
            url: "{{ url('/chairs') }}",
            success: function (data) {
                $('#chair-modal').modal('hide');
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
                $('#btn-save').text('{{ __("common.save_changes") }}');
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').find('ul').append('<li>' + value + '</li>');
                });
            }
        });
    }

    function editRecord(id) {
        $.LoadingOverlay("show");
        $("#chair-form")[0].reset();
        $('#chair_id').val('');
        $('#btn-save').attr('disabled', false);
        $('.alert-danger').hide();

        $.ajax({
            type: 'get',
            url: "{{ url('/chairs') }}/" + id + "/edit",
            success: function (data) {
                $('#chair_id').val(id);
                $('[name="chair_code"]').val(data.chair_code);
                $('[name="chair_name"]').val(data.chair_name);
                $('[name="status"]').val(data.status);
                $('[name="branch_id"]').val(data.branch_id);
                $('[name="notes"]').val(data.notes);
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#chair-modal').modal('show');
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
        $('.alert-danger').hide().find('ul').empty();

        $.ajax({
            type: 'PUT',
            data: $('#chair-form').serialize(),
            url: "{{ url('/chairs') }}/" + $('#chair_id').val(),
            success: function (data) {
                $('#chair-modal').modal('hide');
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
                    $('.alert-danger').find('ul').append('<li>' + value + '</li>');
                });
            }
        });
    }

    function deleteRecord(id) {
        swal({
                title: "{{ __('common.are_you_sure') }}",
                text: "{{ __('chairs.delete_confirm_message') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "{{ __('common.yes_delete_it') }}",
                closeOnConfirm: false,
                cancelButtonText: "{{ __('common.cancel') }}"
            },
            function () {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'delete',
                    data: { _token: CSRF_TOKEN },
                    url: "{{ url('/chairs') }}/" + id,
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
