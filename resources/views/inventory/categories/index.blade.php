@extends('layouts.list-page')

@section('page_title', __('inventory.categories'))
@section('table_id', 'categories-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('inventory.add_category') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('inventory.sn') }}</th>
    <th>{{ __('inventory.category_code') }}</th>
    <th>{{ __('inventory.category_name') }}</th>
    <th>{{ __('inventory.category_type') }}</th>
    <th>{{ __('inventory.items_count') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('inventory.added_by') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('inventory.categories.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'inventory': @json(__('inventory')),
                'common': @json(__('common'))
            });

            dataTable = $('#categories-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/inventory-categories') }}",
                    data: function (d) {}
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'code', name: 'code'},
                    {data: 'name', name: 'name'},
                    {data: 'type_label', name: 'type_label'},
                    {data: 'items_count', name: 'items_count'},
                    {data: 'status', name: 'status', orderable: false, searchable: false},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $("#category-form")[0].reset();
            $('#id').val('');
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#category-modal').modal('show');
        }

        function save_data() {
            var id = $('#id').val();
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
            $.ajax({
                type: 'POST',
                data: $('#category-form').serialize(),
                url: "/inventory-categories",
                success: function (data) {
                    $('#category-modal').modal('hide');
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
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function editRecord(id) {
            $.LoadingOverlay("show");
            $("#category-form")[0].reset();
            $('#id').val('');
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "inventory-categories/" + id + "/edit",
                success: function (data) {
                    $('#id').val(id);
                    $('[name="name"]').val(data.name);
                    $('[name="code"]').val(data.code);
                    $('[name="type"]').val(data.type);
                    $('[name="description"]').val(data.description);
                    $('[name="sort_order"]').val(data.sort_order);
                    $('[name="is_active"]').prop('checked', data.is_active);
                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}');
                    $('#category-modal').modal('show');
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
                data: $('#category-form').serialize(),
                url: "/inventory-categories/" + $('#id').val(),
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
                title: "{{ __('common.are_you_sure') }}",
                text: "{{ __('common.delete_confirm') }}",
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
                    url: "/inventory-categories/" + id,
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
