@extends('layouts.list-page')

@section('page_title', __('leaves.leave_requests'))

@section('table_id', 'leave-requests_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('leaves.request_date') }}</th>
    <th>{{ __('leaves.leave_type') }}</th>
    <th>{{ __('leaves.start_date') }}</th>
    <th>{{ __('leaves.duration') }}</th>
    <th>{{ __('leaves.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
@include('leave_requests.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'leaves': @json(__('leaves')),
            'common': @json(__('common'))
        });

        dataTable = $('#leave-requests_table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/leave-requests') }}",
                data: function (d) {
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
                {data: 'created_at', name: 'created_at'},
                {data: 'name', name: 'name'},
                {data: 'start_date', name: 'start_date'},
                {data: 'duration', name: 'duration'},
                {data: 'status', name: 'status'},
                {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
        loadLeaveTypes();
    });

    function createRecord() {
        $("#leave-requests-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#leave-requests-modal').modal('show');
    }

    function loadLeaveTypes() {
        $.ajax({
            url: "{{ url('/get-all-leave-types') }}",
            dataType: 'json',
            success: function (data) {
                $('#leave_type_id').select2({
                    language: '{{ app()->getLocale() }}',
                    placeholder: "{{ __('leaves.choose_leave_type') }}",
                    data: data,
                    allowClear: true
                });
            },
            error: function () {
                console.error('{{ __("leaves.failed_to_load_leave_types") }}');
            }
        });
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
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'POST',
            data: $('#leave-requests-form').serialize(),
            url: "{{ url('/leave-requests') }}",
            success: function (data) {
                $('#leave-requests-modal').modal('hide');
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
        $("#leave-requests-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);

        $.ajax({
            type: 'get',
            url: "{{ url('/leave-requests') }}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="start_date"]').val(data.start_date);
                $('[name="duration"]').val(data.duration);
                let leave_type = {
                    id: data.leave_type_id,
                    text: data.name
                };
                let newOption = new Option(leave_type.text, leave_type.id, true, true);
                $('#leave_type_id').append(newOption).trigger('change');

                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#leave-requests-modal').modal('show');
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
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#leave-requests-form').serialize(),
            url: "{{ url('/leave-requests') }}/" + $('#id').val(),
            success: function (data) {
                $('#leave-requests-modal').modal('hide');
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
            text: "{{ __('leaves.confirm_delete_request') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: {_token: CSRF_TOKEN},
                url: "{{ url('/leave-requests') }}/" + id,
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
