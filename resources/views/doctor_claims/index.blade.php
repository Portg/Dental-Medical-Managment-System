@extends('layouts.list-page')

@section('page_title', __('doctor_claims.title'))
@section('table_id', 'doctor_claims_table')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('doctor_claims.date') }}</th>
    <th>{{ __('doctor_claims.patient') }}</th>
    <th>{{ __('doctor_claims.doctor') }}</th>
    <th>{{ __('doctor_claims.treatment_amount') }}</th>
    <th>{{ __('doctor_claims.insurance_claim') }}</th>
    <th>{{ __('doctor_claims.cash_claim') }}</th>
    <th>{{ __('doctor_claims.total_claim_amount') }}</th>
    <th>{{ __('doctor_claims.payment_balance') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('doctor_claims.create')
    @include('doctor_claims.payments.create')
@endsection

@section('page_js')
<script type="text/javascript">
    let save = false;
    $(function () {

        dataTable = $('#doctor_claims_table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/doctor-claims/') }}",
                data: function (d) {
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true},
                {data: 'created_at', name: 'created_at'},
                {data: 'patient', name: 'patient'},
                {data: 'doctor', name: 'doctor'},
                {data: 'claim_amount', name: 'claim_amount'},
                {data: 'insurance_amount', name: 'insurance_amount'},
                {data: 'cash_amount', name: 'cash_amount'},
                {data: 'total_claim_amount', name: 'total_claim_amount'},
                {data: 'payment_balance', name: 'payment_balance'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();

    });

    function Approve_Claim(id, claim_amount) {
        save = true;
        $("#claims-form")[0].reset();
        $('#id').val(''); ///always reset hidden form fields
        $('#btn-save').attr('disabled', false);
        $('#id').val(id);
        $('#claim_amount').val(claim_amount);
        $('#insurance_amount').val(0);
        $('#cash_amount').val(0);

        $('#btn-save').text('{{ __("common.save_changes") }}');
        $('#claims-modal').modal('show');
    }

    function save_data() {

        //check save method
        if (save) {
            //check the amount
            var insurance = $('#insurance_amount').val();
            var cash = $('#cash_amount').val();
            var amount = Number(insurance) + Number(cash);

            if (amount === $('#claim_amount').val()) {
                save_new_record();
            } else {
                $('#claims-modal').modal('hide');
                swal('{{ __("common.alert") }}', '{{ __("doctor_claims.amounts_not_matching") }}');
            }

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
            data: $('#claims-form').serialize(),
            url: "/doctor-claims",
            success: function (data) {
                $('#claims-modal').modal('hide');
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
                $('#btn-save').text('{{ __("common.save_changes") }}');
                $('#rate-modal').modal('show');

                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }


    function editRecord(id) {
        save = false;
       $.LoadingOverlay("show");
        $("#claims-form")[0].reset();
        $('#id').val(''); ///always reset hidden form fields
        $('#btn-save').attr('disabled', false);
        $.ajax({
            type: 'get',
            url: "doctor-claims/" + id + "/edit",
            success: function (data) {
                console.log(data);
                $('#id').val(id);
                $('[name="insurance_amount"]').val(data.insurance_amount);
                $('[name="cash_amount"]').val(data.cash_amount);
                $('[name="claim_amount"]').val(data.claim_amount);
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
            url: "/doctor-claims/" + $('#id').val(),
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


    function record_payment(id, amount) {
        $("#payment-form")[0].reset();
        $('#id').val(''); ///always reset hidden form fields
        $('#btn-save').attr('disabled', false);
        $('#claim_id').val(id);
        $('#amount').val(amount);
        $('#btn-save').text('{{ __("common.save_changes") }}');
        $('#payment-modal').modal('show');
    }

    function save_payment_record() {
       $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $.ajax({
            type: 'POST',
            data: $('#payment-form').serialize(),
            url: "/claims-payment",
            success: function (data) {
                $('#payment-modal').modal('hide');
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
                $('#btn-save').text('{{ __("common.save_changes") }}');
                $('#payment-modal').modal('show');

                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    /**
     * Local override of alert_dialog — only shows swal on success (truthy status),
     * silently redraws the table without swal on failure.
     */
    function alert_dialog(message, status) {
        if (status) {
            let oTable = $('#doctor_claims_table').dataTable();
            oTable.fnDraw(false);
            swal("{{ __('common.alert') }}", message, status);
        }
    }
</script>
@endsection
