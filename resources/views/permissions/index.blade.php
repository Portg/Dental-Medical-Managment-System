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
                    <span class="caption-subject">{{ __('menu.user_management') }} / {{ __('permissions.list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-hover" id="permissions-table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('permissions.name') }}</th>
                        <th>{{ __('permissions.slug') }}</th>
                        <th>{{ __('permissions.module') }}</th>
                        <th>{{ __('permissions.description') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
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
@include('permissions.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'permissions': @json(__('permissions'))
        });

        var table = $('#permissions-table').DataTable({
            destroy: true,
            processing: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/permissions/') }}",
            },
            dom: 'Bfrtip',
            buttons: {
                buttons: [
                    {extend: 'pdfHtml5', className: 'pdfButton'},
                    {extend: 'excelHtml5', className: 'excelButton'},
                ]
            },
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

    //general alert dialog
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