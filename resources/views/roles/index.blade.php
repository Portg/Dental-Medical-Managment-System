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
                    <span class="caption-subject">{{ __('menu.user_management') }} / {{ __('roles.list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="#"
                                   onclick="createRecord()"> {{ __('common.add_new') }} <i
                                        class="fa fa-plus"></i> </a>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('roles.name') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
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
@include('roles.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'roles': @json(__('roles'))
            });

            var table = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                language: LanguageManager.getDataTableLang(),
                // serverSide: true,
                ajax: {
                    url: "{{ url('/roles/') }}",
                    data: function (d) {
                        // d.email = $('.searchEmail').val(),
                        //     d.search = $('input[type="search"]').val()
                    }
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
                    {data: 'created_at', name: 'created_at'},
                    {data: 'name', name: 'name'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });


        });

        function createRecord() {
            $("#roles-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_record") }}');
            $('#roles-modal').modal('show');
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
                data: $('#roles-form').serialize(),
                url: "/roles",
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
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "roles/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="name"]').val(data.name);
                   $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
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
                url: "/roles/" + $('#id').val(),
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
                        url: "roles/" + id,
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





