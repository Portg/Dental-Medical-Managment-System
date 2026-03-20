$(document).ready(function (e) {
    $('#branch_block').hide();
});

$(function () {
    var columns = [
        {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
        {data: 'username', name: 'username'}
    ];

    if (window.UsersConfig.isZhCN) {
        columns.push({data: 'full_name', name: 'full_name'});
    } else {
        columns.push({data: 'surname', name: 'surname'});
        columns.push({data: 'othername', name: 'othername'});
    }

    columns = columns.concat([
        {data: 'email', name: 'email', 'visible': true},
        {data: 'phone_no', name: 'phone_no'},
        {data: 'user_role', name: 'user_role'},
        {data: 'branch', name: 'branch'},
        {data: 'is_doctor', name: 'is_doctor'},
        {data: 'status_label', name: 'status_label'},
        {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
        {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
    ]);

    dataTable = $('#users_table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: { url: window.UsersConfig.usersUrl },
        dom: 'rtip',
        columns: columns
    });

    setupEmptyStateHandler();
});

function createRecord() {
    $("#users-form")[0].reset();
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $('#btn-save').text(LanguageManager.trans('common.save_record'));
    $('.password_config').show();
    $('.status_config').hide();
    $('.reactivate_password').hide();
    $('#status_action').val('');
    $('#users-modal').modal('show');
}

$('#role').select2({
    language: window.UsersConfig.locale,
    placeholder: LanguageManager.trans('users.select_role'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-role',
        dataType: 'json',
        delay: 300,
        data: function (params) { return { q: $.trim(params.term) }; },
        processResults: function (data) { return { results: data }; },
        cache: true
    }
}).on('change', function (e) {
    let data = $('#role').select2('data');
    let slug = data.length ? data[0].slug : '';
    if (slug === 'super-admin') {
        $('#branch_block').hide();
    } else {
        $('#branch_block').show();
    }
});

$('#branch_id').select2({
    language: window.UsersConfig.locale,
    placeholder: LanguageManager.trans('users.choose_branch'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-branch',
        dataType: 'json',
        delay: 300,
        data: function (params) { return { q: $.trim(params.term) }; },
        processResults: function (data) { return { results: data }; },
        cache: true
    }
});

$(document).on('change', '#status_action', function() {
    if ($(this).val() === 'active') {
        $('.reactivate_password').show();
    } else {
        $('.reactivate_password').hide();
        $('[name="new_password"]').val('');
    }
});

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
    $('#btn-save').text(LanguageManager.trans('common.processing'));
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
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
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
    $('#id').val('');
    $('#btn-save').attr('disabled', false);
    $.ajax({
        type: 'get',
        url: "users/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            if (window.UsersConfig.isZhCN) {
                $('[name="full_name"]').val((data.surname || '') + (data.othername || ''));
            } else {
                $('[name="surname"]').val(data.surname);
                $('[name="othername"]').val(data.othername);
            }
            $('[name="username"]').val(data.username);
            $('[name="email"]').val(data.email);
            $('[name="phone_no"]').val(data.phone_no);
            $('[name="alternative_no"]').val(data.alternative_no);
            $('[name="nin"]').val(data.nin);
            $('input[name^="is_doctor"][value="' + data.is_doctor + '"').prop('checked', true);
            $('.password_config').hide();

            $('.status_config').show();
            $('#status_action').val('');
            $('.reactivate_password').hide();
            var statusText = data.status === 'active'
                ? LanguageManager.trans('users.status_active')
                : LanguageManager.trans('users.status_resigned');
            $('#status_action').closest('.form-group').find('.help-block').remove();
            $('#status_action').closest('.form-group').append(
                '<span class="help-block text-muted">' + LanguageManager.trans('users.current_status') + ': ' + statusText + '</span>'
            );

            let role_data = { id: data.role_id, text: data.user_role };
            let newOption = new Option(role_data.text, role_data.id, true, true);
            $('#role').append(newOption).trigger('change');

            if (data.branch_id == null) {
                $('#branch_id').val([]).trigger('change');
            } else {
                let branch_data = { id: data.branch_id, text: data.branch };
                let branchOption = new Option(branch_data.text, branch_data.id, true, true);
                $('#branch_id').append(branchOption).trigger('change');
            }

            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
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
    $('#btn-save').text(LanguageManager.trans('common.updating'));

    var userId = $('#id').val();
    var statusAction = $('#status_action').val();

    $.ajax({
        type: 'PUT',
        data: $('#users-form').serialize(),
        url: "/users/" + userId,
        success: function (data) {
            if (!data.status) {
                $('#users-modal').modal('hide');
                alert_dialog(data.message, "danger");
                $.LoadingOverlay("hide");
                return;
            }

            if (statusAction) {
                $.ajax({
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        status: statusAction,
                        new_password: $('[name="new_password"]').val()
                    },
                    url: "/users/" + userId + "/change-status",
                    success: function (statusData) {
                        $('#users-modal').modal('hide');
                        if (statusData.status) {
                            alert_dialog(statusData.message, "success");
                        } else {
                            alert_dialog(statusData.message, "danger");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function () {
                        $.LoadingOverlay("hide");
                        alert_dialog(LanguageManager.trans('users.status_change_failed'), "danger");
                    }
                });
            } else {
                $('#users-modal').modal('hide');
                alert_dialog(data.message, "success");
                $.LoadingOverlay("hide");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.update_record'));
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
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('users.delete_confirm_message'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            closeOnConfirm: false
        },
        function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
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
