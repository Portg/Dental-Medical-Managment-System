@extends('layouts.list-page')

@section('page_title', __('claim_rates.claim_rates_form'))
@section('table_id', 'sample_1')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('doctor_claims.date') }}</th>
    <th>{{ __('doctor_claims.patient') }}</th>
    <th>{{ __('doctor_claims.treatment_amount') }}</th>
    <th>{{ __('doctor_claims.insurance_claim') }}</th>
    <th>{{ __('doctor_claims.cash_claim') }}</th>
    <th>{{ __('doctor_claims.total_claim_amount') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('doctor::claims.edit_claim')
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            // Load page-specific translations
            LanguageManager.loadAllFromPHP({
                'doctor_claims': @json(__('doctor_claims'))
            });

            dataTable = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/claims/') }}",
                    data: function (d) {
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'patient', name: 'patient'},
                    {data: 'claim_amount', name: 'claim_amount'},
                    {data: 'insurance_amount', name: 'insurance_amount'},
                    {data: 'cash_amount', name: 'cash_amount'},
                    {data: 'total_claim_amount', name: 'total_claim_amount'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });


        function editRecord(id) {
           $.LoadingOverlay("show");
            $("#claims-form")[0].reset();
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "claims/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="amount"]').val(data.claim_amount);
                   $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
                    $('#claims-modal').modal('show');

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
                data: $('#claims-form').serialize(),
                url: "/claims/" + $('#id').val(),
                success: function (data) {
                    $('#claims-modal').modal('hide');
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
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('doctor_claims.delete_confirm_message') }}",
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
                        url: "/claims/" + id,
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

        // Override base template alert_dialog with page-specific behavior
        function alert_dialog(message, status) {

            if (status) {
                dataTable.draw(false);
                swal("{{ __('common.alert') }}", message, status);
            }
        }
    </script>
@endsection
