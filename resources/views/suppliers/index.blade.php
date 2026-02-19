@extends('layouts.list-page')

@section('page_title', __('suppliers.expenses_mgt_suppliers'))
@section('table_id', 'suppliers-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('suppliers.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('suppliers.id') }}</th>
    <th>{{ __('suppliers.date') }}</th>
    <th>{{ __('suppliers.name') }}</th>
    <th>{{ __('suppliers.added_by') }}</th>
    <th>{{ __('suppliers.edit') }}</th>
    <th>{{ __('suppliers.delete') }}</th>
@endsection

@section('modals')
    @include('suppliers.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'suppliers': @json(__('suppliers'))
            });

            dataTable = $('#suppliers-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/suppliers/') }}",
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'name', name: 'name'},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $("#supplier-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#supplier-modal').modal('show');
        }

        function save_data() {
            //check save method
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
                data: $('#supplier-form').serialize(),
                url: "/suppliers",
                success: function (data) {
                    $('#supplier-modal').modal('hide');
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
                    $('#supplier-modal').modal('show');

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
            $("#supplier-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "suppliers/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="name"]').val(data.name);
                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
                    $('#supplier-modal').modal('show');

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
                data: $('#supplier-form').serialize(),
                url: "/suppliers/" + $('#id').val(),
                success: function (data) {
                    $('#supplier-modal').modal('hide');
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
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('suppliers.delete_confirm_message') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('common.yes_delete_it') }}",
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
                        url: "/suppliers/" + id,
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
