@extends('layouts.list-page')

@section('page_title', __('menu.user_management') . ' / ' . __('role_permissions.title'))
@section('table_id', 'role_permissions-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('role_permissions.role') }}</th>
    <th>{{ __('role_permissions.permission') }}</th>
    <th>{{ __('role_permissions.permission_slug') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('role-permissions.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'role_permissions': @json(__('role_permissions'))
            });

            dataTable = $('#role_permissions-table').DataTable({
                processing: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/role-permissions/') }}",
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'role_name', name: 'role_name'},
                    {data: 'permission_name', name: 'permission_name'},
                    {data: 'permission_slug', name: 'permission_slug'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $('#role_permission_id').val('');
            $('#role_id').val('').trigger('change');
            $('#permission_id').val('').trigger('change');
            $('#modal_title').html('{{ __("role_permissions.add_new") }}');
            $('#create_modal').modal('show');
        }

        function editRecord(id) {
            $.ajax({
                url: "{{ url('role-permissions') }}/" + id + "/edit",
                type: 'GET',
                success: function (data) {
                    $('#role_permission_id').val(data.id);
                    $('#role_id').val(data.role_id).trigger('change');
                    $('#permission_id').val(data.permission_id).trigger('change');
                    $('#modal_title').html('{{ __("role_permissions.edit") }}');
                    $('#create_modal').modal('show');
                }
            });
        }

        function deleteRecord(id) {
            if (confirm("{{ __('role_permissions.confirm_delete') }}")) {
                $.ajax({
                    url: "{{ url('role-permissions') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (data) {
                        if (data.status) {
                            alert(data.message);
                            $('#role_permissions-table').DataTable().ajax.reload();
                        } else {
                            alert(data.message);
                        }
                    }
                });
            }
        }

        $('#save_role_permission').click(function () {
            var id = $('#role_permission_id').val();
            var url = id ? "{{ url('role-permissions') }}/" + id : "{{ url('role-permissions') }}";
            var type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: type,
                data: {
                    _token: '{{ csrf_token() }}',
                    role_id: $('#role_id').val(),
                    permission_id: $('#permission_id').val()
                },
                success: function (data) {
                    if (data.status) {
                        alert(data.message);
                        $('#create_modal').modal('hide');
                        $('#role_permissions-table').DataTable().ajax.reload();
                    } else {
                        alert(data.message);
                    }
                }
            });
        });
    </script>
@endsection
