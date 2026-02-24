@extends('layouts.list-page')

@section('page_title', __('salary_advances.page_title'))

@section('table_id', 'salary-advances-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('salary_advances.id') }}</th>
    <th>{{ __('salary_advances.employee') }}</th>
    <th>{{ __('salary_advances.classification') }}</th>
    <th>{{ __('salary_advances.payslip_month') }}</th>
    <th>{{ __('salary_advances.amount') }}</th>
    <th>{{ __('salary_advances.payment_method') }}</th>
    <th>{{ __('salary_advances.payment_date') }}</th>
    <th>{{ __('salary_advances.added_by') }}</th>
    <th>{{ __('salary_advances.edit') }}</th>
    <th>{{ __('salary_advances.delete') }}</th>
@endsection

@section('modals')
@include('salary_advances.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'salary_advances': @json(__('salary_advances')),
            'common': @json(__('common'))
        });

        dataTable = $('#salary-advances-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/salary-advances/') }}",
                data: function (d) {
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'employee', name: 'employee'},
                {data: 'payment_classification', name: 'payment_classification'},
                {data: 'advance_month', name: 'advance_month'},
                {data: 'amount', name: 'amount'},
                {data: 'payment_method', name: 'payment_method'},
                {data: 'payment_date', name: 'payment_date'},
                {data: 'addedBy', name: 'addedBy'},
                {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false},
                {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#scale-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __('salary_advances.save_changes') }}');
        $('#scale-modal').modal('show');
    }

    // Select2 employee search
    $('#employee').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('salary_advances.choose_employee') }}",
        minimumInputLength: 2,
        ajax: {
            url: '/search-employee',
            dataType: 'json',
            delay: 300,
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
        $('#btn-save').text('{{ __('salary_advances.processing') }}');
        $.ajax({
            type: 'POST',
            data: $('#scale-form').serialize(),
            url: "/salary-advances",
            success: function (data) {
                $('#scale-modal').modal('hide');
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
                $('#btn-save').text('{{ __('salary_advances.save_changes') }}');
                $('#scale-modal').modal('show');

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
        $("#scale-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $.ajax({
            type: 'get',
            url: "salary-advances/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                $('[name="amount"]').val(data.advance_amount);
                $('[name="advance_month"]').val(data.advance_month);
                $('[name="payment_date"]').val(data.payment_date);
                let employee_data = {
                    id: data.employee_id,
                    text: LanguageManager.joinName(data.surname, data.othername)
                };
                let newOption = new Option(employee_data.text, employee_data.id, true, true);
                $('#employee').append(newOption).trigger('change');
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __('salary_advances.update_record') }}')
                $('#scale-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __('salary_advances.updating') }}');
        $.ajax({
            type: 'PUT',
            data: $('#scale-form').serialize(),
            url: "/salary-advances/" + $('#id').val(),
            success: function (data) {
                $('#scale-modal').modal('hide');
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
                $.LoadingOverlay("hide");
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
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
                title: "{{ __('salary_advances.are_you_sure') }}",
                text: "{{ __('salary_advances.cannot_recover_advance') }}",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "{{ __('salary_advances.yes_delete') }}",
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
                    url: "/salary-advances/" + id,
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
