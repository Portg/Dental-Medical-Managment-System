@extends('layouts.list-page')

@section('page_title', __('holidays.title'))

@section('table_id', 'holidays_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('holidays.holiday_name') }}</label>
                <input type="text" class="form-control" id="filter_name" placeholder="{{ __('holidays.enter_holiday_name') }}">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('holidays.repeat_every_year') }}</label>
                <select class="form-control" id="filter_repeat">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="Yes">{{ __('common.yes') }}</option>
                    <option value="No">{{ __('common.no') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-3 text-right filter-actions">
            <div class="form-group">
                <div>
                    <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('holidays.added_date') }}</th>
    <th>{{ __('holidays.holiday_name') }}</th>
    <th>{{ __('holidays.date_of_the_year') }}</th>
    <th>{{ __('holidays.repeat_every_year') }}</th>
    <th>{{ __('holidays.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('holidays.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'holidays': @json(__('holidays')),
            'common': @json(__('common'))
        });

        dataTable = $('#holidays_table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/holidays') }}",
                data: function (d) {
                    d.filter_name = $('#filter_name').val();
                    d.filter_repeat = $('#filter_repeat').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true, width: '50px'},
                {data: 'created_at', name: 'created_at', width: '160px'},
                {data: 'name', name: 'name'},
                {data: 'holiday_date', name: 'holiday_date', width: '120px'},
                {data: 'repeat_date', name: 'repeat_date', width: '100px'},
                {data: 'addedBy', name: 'addedBy', width: '100px'},
                {data: 'action', name: 'action', orderable: false, searchable: false, width: '90px'}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#holidays-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#holidays-modal').modal('show');
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
            data: $('#holidays-form').serialize(),
            url: "{{ url('/holidays') }}",
            success: function (data) {
                $('#holidays-modal').modal('hide');
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
        $("#holidays-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);

        $.ajax({
            type: 'get',
            url: "{{ url('/holidays') }}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="name"]').val(data.name);
                $('[name="holiday_date"]').val(data.holiday_date);
                $('input[name^="repeat_date"][value="' + data.repeat_date + '"]').prop('checked', true);

                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#holidays-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("holidays.updating") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#holidays-form').serialize(),
            url: "{{ url('/holidays') }}/" + $('#id').val(),
            success: function (data) {
                $('#holidays-modal').modal('hide');
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
            text: "{{ __('holidays.delete_confirm_message') }}",
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
                url: "{{ url('/holidays') }}/" + id,
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
