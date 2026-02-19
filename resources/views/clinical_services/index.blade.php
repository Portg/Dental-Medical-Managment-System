@extends('layouts.list-page')

@section('page_title', __('clinical_services.title'))
@section('table_id', 'services-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('clinical_services.procedure') }}</th>
    <th>{{ __('clinical_services.price') }}</th>
    <th>{{ __('clinical_services.add_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('clinical_services.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'clinical_services': @json(__('clinical_services')),
            'common': @json(__('common'))
        });

        dataTable = $('#services-table').DataTable({
            destroy: true,
            processing: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/clinic-services/') }}",
                data: function (d) {
                    d.search = $('input[type="search"]').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'price', name: 'price'},
                {data: 'addedBy', name: 'addedBy'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#service-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#service-modal').modal('show');
    }

    function save_data() {
        if ($('#id').val() === "") {
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
            data: $('#service-form').serialize(),
            url: "{{ url('/clinic-services') }}",
            success: function (data) {
                $('#service-modal').modal('hide');
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
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.update_record") }}');
        $("#service-form")[0].reset();
        $('#id').val('');

        $.ajax({
            type: 'get',
            url: "{{ url('/clinic-services') }}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="name"]').val(data.name);
                $('[name="price"]').val(data.price);
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#service-modal').modal('show');
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
            data: $('#service-form').serialize(),
            url: "{{ url('/clinic-services') }}/" + $('#id').val(),
            success: function (data) {
                $('#service-modal').modal('hide');
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
            text: "{{ __('clinical_services.delete_confirm_message') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            closeOnConfirm: false,
            cancelButtonText: "{{ __('common.cancel') }}"
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: CSRF_TOKEN },
                url: "{{ url('/clinic-services') }}/" + id,
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
