@extends('layouts.list-page')

@section('page_title', __('birthday_wishes.birthday_wishes_title'))

@section('table_id', 'wishes-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{__('common.add_new')}}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('birthday_wishes.message') }}</th>
    <th>{{ __('birthday_wishes.added_by') }}</th>
    <th>{{ __('birthday_wishes.edit') }}</th>
    <th>{{ __('birthday_wishes.delete') }}</th>
@endsection

@section('empty_icon', 'fa-birthday-cake')
@section('empty_title', __('birthday_wishes.no_wishes_found'))

@section('modals')
    @include('birthday_wishes.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        dataTable = $(getTableSelector()).DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/birthday-wishes/') }}",
                data: function (d) {
                    d.search = $('input[type="search"]').val()
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                {data: 'message', name: 'message'},
                {data: 'addedBy', name: 'addedBy'},
                {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#wishes-form")[0].reset();
        $('#id').val('');

        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');

        $('#wishes-modal').modal('show');
    }

    /**
     * 保存数据（新记录或更新记录）
     */
    function save_data() {
        if ($('#id').val() === "") {
            save_new_record();
        } else {
            update_record();
        }
    }

    /**
     * 保存新记录
     */
    function save_new_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $.ajax({
            type: 'POST',
            data: $('#wishes-form').serialize(),
            url: "/birthday-wishes",
            success: function (data) {
                $('#wishes-modal').modal('hide');
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

    /**
     * 编辑记录
     * @param id 记录 ID
     */
    function editRecord(id) {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.update_record") }}');
        $("#wishes-form")[0].reset();
        $('#id').val('');
        $.ajax({
            type: 'get',
            url: "birthday-wishes/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="message"]').val(data.message);
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}')
                $('#wishes-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    /**
     * 更新记录
     */
    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.updating") }}');
        $.ajax({
            type: 'PUT',
            data: $('#wishes-form').serialize(),
            url: "/birthday-wishes/" + $('#id').val(),
            success: function (data) {
                $('#wishes-modal').modal('hide');
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

    /**
     * 删除记录
     * @param id 记录 ID
     */
    function deleteRecord(id) {
        swal({
                title: '{{ __("common.are_you_sure") }}',
                text: '{{ __("birthday_wishes.unrecoverable_warning") }}',
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: '{{ __("common.yes_delete_it") }}',
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
                    url: "birthday-wishes/" + id,
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
