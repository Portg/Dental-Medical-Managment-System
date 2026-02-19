@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('sms.sms_credit_loading'))
@section('table_id', 'leave-types_table')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <h2 class="text-primary align-content-center" style="margin: 0; display: inline-block;"> {{ __('sms.sms_balance') }}: {{ $current_balance }}</h2>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('sms.id') }}</th>
    <th>{{ __('sms.transaction_date') }}</th>
    <th>{{ __('sms.transaction_reference') }}</th>
    <th>{{ __('sms.credit_amount') }}</th>
    <th>{{ __('sms.loaded_by') }}</th>
@endsection

{{-- ========================================================================
     Modals
     ======================================================================== --}}
@section('modals')
    @include('sms_transactions.create')
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
    <script type="text/javascript">
        $(function () {
            dataTable = $('#leave-types_table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/sms-transactions/') }}",
                    data: function (d) {
                        // d.email = $('.searchEmail').val(),
                        //     d.search = $('input[type="search"]').val()
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'type', name: 'type'},
                    {data: 'amount', name: 'amount'},
                    {data: 'addedBy', name: 'addedBy'}
                ]
            });

            setupEmptyStateHandler();
        });

        function createRecord() {
            $("#leave-types-form")[0].reset();
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_record") }}');
            $('#leave-types-modal').modal('show');
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
                data: $('#leave-types-form').serialize(),
                url: "/leave-types",
                success: function (data) {
                    $('#leave-types-modal').modal('hide');
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
            $("#leave-types-form")[0].reset();
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "leave-types/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="name"]').val(data.name);
                    $('[name="max_days"]').val(data.max_days);

                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
                    $('#leave-types-modal').modal('show');

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
                data: $('#leave-types-form').serialize(),
                url: "/leave-types/" + $('#id').val(),
                success: function (data) {
                    $('#leave-types-modal').modal('hide');
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
                    text: "{{ __('common.delete_warning') }}",
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
                        url: "leave-types/" + id,
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
