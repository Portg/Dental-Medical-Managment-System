@extends('layouts.list-page')

@section('page_title', __('menu.user_management') . ' / ' . __('permissions.list'))
@section('table_id', 'permissions-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('permissions.name') }}</th>
    <th>{{ __('permissions.slug') }}</th>
    <th>{{ __('permissions.module') }}</th>
    <th>{{ __('permissions.description') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('permissions.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'permissions': @json(__('permissions'))
            });

            dataTable = $('#permissions-table').DataTable({
                processing: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/permissions/') }}",
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'name', name: 'name'},
                    {data: 'slug', name: 'slug'},
                    {data: 'module', name: 'module'},
                    {data: 'description', name: 'description'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $('#permission_id').val('');
            $('#name').val('');
            $('#slug').val('');
            $('#module').val('');
            $('#description').val('');
            $('#modal_title').html('{{ __("permissions.add_permission") }}');
            $('#create_modal').modal('show');
        }

        function editRecord(id) {
            $.ajax({
                url: "{{ url('permissions') }}/" + id + "/edit",
                type: 'GET',
                success: function (data) {
                    $('#permission_id').val(data.id);
                    $('#name').val(data.name);
                    $('#slug').val(data.slug);
                    $('#module').val(data.module);
                    $('#description').val(data.description);
                    $('#modal_title').html('{{ __("permissions.edit_permission") }}');
                    $('#create_modal').modal('show');
                }
            });
        }

        function deleteRecord(id) {
            if (confirm(LanguageManager.trans('messages.confirm_delete_permission', "{{ __('messages.confirm_delete_permission') }}"))) {
                $.ajax({
                    url: "{{ url('permissions') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (data) {
                        if (data.status) {
                            alert_dialog(data.message, "success");
                            $('#permissions-table').DataTable().ajax.reload();
                        } else {
                            alert_dialog(data.message, "danger");
                        }
                    }
                });
            }
        }

        $('#save_permission').click(function () {
            var id = $('#permission_id').val();
            var url = id ? "{{ url('permissions') }}/" + id : "{{ url('permissions') }}";
            var type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: type,
                data: {
                    _token: '{{ csrf_token() }}',
                    name: $('#name').val(),
                    slug: $('#slug').val(),
                    module: $('#module').val(),
                    description: $('#description').val()
                },
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                        $('#create_modal').modal('hide');
                        $('#permissions-table').DataTable().ajax.reload();
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                }
            });
        });

        //general alert dialog - custom toastr-based (overrides base template's swal-based one)
        function alert_dialog(message, status) {
            toastr[status](message);
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": false,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            }
        }
    </script>
@endsection
