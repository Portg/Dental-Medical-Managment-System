@extends('layouts.list-page')

@section('page_title', __('menu.user_management') . ' / ' . __('users.list'))
@section('table_id', 'users_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    @if(app()->getLocale() === 'zh-CN')
        <th>{{ __('users.full_name') }}</th>
    @else
        <th>{{ __('users.surname') }}</th>
        <th>{{ __('users.othername') }}</th>
    @endif
    <th>{{ __('users.email') }}</th>
    <th>{{ __('users.phone_no') }}</th>
    <th>{{ __('users.role') }}</th>
    <th>{{ __('users.branch') }}</th>
    <th>{{ __('users.is_doctor') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('users.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(document).ready(function (e) {
        $('#branch_block').hide();
    });
    $(function () {
        // Load page-specific translations
        LanguageManager.loadAllFromPHP({
            'users': @json(__('users'))
        });

        dataTable = $('#users_table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/users/') }}",
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                @if(app()->getLocale() === 'zh-CN')
                {data: 'full_name', name: 'full_name'},
                @else
                {data: 'surname', name: 'surname'},
                {data: 'othername', name: 'othername'},
                @endif
                {data: 'email', name: 'email', 'visible': true},
                {data: 'phone_no', name: 'phone_no'},
                {data: 'user_role', name: 'user_role'},
                {data: 'branch', name: 'branch'},
                {data: 'is_doctor', name: 'is_doctor'},
                {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#users-form")[0].reset();
        $('#id').val(''); ///always reset hidden form fields
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('.password_config').show();
        $('#users-modal').modal('show');
    }


    //filter roles
    $('#role').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('users.select_role') }}",
        minimumInputLength: 2,
        ajax: {
            url: '/search-role',
            dataType: 'json',
            data: function (params) {
                return {
                    q: $.trim(params.term)
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    }).on('change', function (e) {
        let selectedRole = $("#role option:selected").text();
        if (selectedRole == "Super Administrator") {
            $('#branch_block').hide();
        } else {
            $('#branch_block').show();
        }
    });


    //filter branches
    $('#branch_id').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('users.choose_branch') }}",
        minimumInputLength: 2,
        ajax: {
            url: '/search-branch',
            dataType: 'json',
            data: function (params) {
                return {
                    q: $.trim(params.term)
                };
            },
            processResults: function (data) {
                console.log(data);
                return {
                    results: data
                };
            },
            cache: true
        }
    });

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
            data: $('#users-form').serialize(),
            url: "/users",
            success: function (data) {
                $('#users-modal').modal('hide');
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
        $("#users-form")[0].reset();
        $('#id').val(''); ///always reset hidden form fields
        $('#btn-save').attr('disabled', false);
        $.ajax({
            type: 'get',
            url: "users/" + id + "/edit",
            success: function (data) {
                // console.log(data);
                $('#id').val(id);
                @if(app()->getLocale() === 'zh-CN')
                $('[name="full_name"]').val((data.surname || '') + (data.othername || ''));
                @else
                $('[name="surname"]').val(data.surname);
                $('[name="othername"]').val(data.othername);
                @endif
                $('[name="email"]').val(data.email);
                $('[name="phone_no"]').val(data.phone_no);
                $('[name="alternative_no"]').val(data.alternative_no);
                $('[name="nin"]').val(data.nin);
                $('input[name^="is_doctor"][value="' + data.is_doctor + '"').prop('checked', true);
                $('.password_config').hide();
                let role_data = {
                    id: data.role_id,
                    text: data.user_role
                };
                let newOption = new Option(role_data.text, role_data.id, true, true);
                $('#role').append(newOption).trigger('change');


                let branch_data = {
                    id: data.branch_id,
                    text: data.branch
                };

                if (data.branch_id == null) {
                    $('#branch_id').val([]).trigger('change');
                } else {
                    let branchOption = new Option(branch_data.text, branch_data.id, true, true);
                    $('#branch_id').append(branchOption).trigger('change');
                }


                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}')
                $('#users-modal').modal('show');

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
            data: $('#users-form').serialize(),
            url: "/users/" + $('#id').val(),
            success: function (data) {
                $('#users-modal').modal('hide');
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
                text: "{{ __('users.delete_confirm_message') }}",
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
                    url: "users/" + id,
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
