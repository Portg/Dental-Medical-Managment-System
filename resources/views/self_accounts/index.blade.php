@extends('layouts.list-page')

@section('page_title', __('self_accounts.accounting_manager_self_accounts'))
@section('table_id', 'self-accounts-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('self_accounts.account_no') }}</th>
    <th>{{ __('self_accounts.account_name') }}</th>
    <th>{{ __('self_accounts.phone_no') }}</th>
    <th>{{ __('common.email') }}</th>
    <th>{{ __('self_accounts.account_balance') }}</th>
    <th>{{ __('self_accounts.added_by') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('self_accounts.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {

            dataTable = $('#self-accounts-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/self-accounts/') }}",
                    data: function (d) {
                        d.search = $('input[type="search"]').val()
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'account_no', name: 'account_no', 'visible': false},
                    {data: 'account_holder', name: 'account_holder'},
                    {data: 'holder_phone_no', name: 'holder_phone_no'},
                    {data: 'holder_email', name: 'holder_email'},
                    {data: 'account_balance', name: 'account_balance'},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'status', name: 'status'},
                    {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                    {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $("#company-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __('common.save_changes') }}');
            $('#company-modal').modal('show');
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
            $('#btn-save').text('{{ __('common.processing') }}');
            $.ajax({
                type: 'POST',
                data: $('#company-form').serialize(),
                url: "/self-accounts",
                success: function (data) {
                    $('#company-modal').modal('hide');
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
                    $('#btn-save').text('{{ __('common.save_changes') }}');
                    $('#company-modal').modal('show');
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
            $("#company-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "self-accounts/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="name"]').val(data.account_holder);
                    $('[name="email"]').val(data.holder_email);
                    $('[name="phone_no"]').val(data.holder_phone_no);
                    $('[name="address"]').val(data.holder_address);
                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __('common.update_record') }}')
                    $('#company-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function update_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('{{ __('common.updating') }}');
            $.ajax({
                type: 'PUT',
                data: $('#company-form').serialize(),
                url: "/self-accounts/" + $('#id').val(),
                success: function (data) {
                    $('#company-modal').modal('hide');
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
                    $('#btn-save').text('{{ __('common.save_changes') }}');
                    $('#company-modal').modal('show');
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
                    text: "{{ __('self_accounts.confirm_delete_account') }}",
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
                        url: "self-accounts/" + id,
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
