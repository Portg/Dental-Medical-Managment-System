@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('claim_rates.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('claim_rates.date') }}</th>
                        <th>{{ __('claim_rates.surname') }}</th>
                        <th>{{ __('claim_rates.other_name') }}</th>
                        <th>{{ __('claim_rates.cash_rate') }}</th>
                        <th>{{ __('claim_rates.insurance_rate') }}</th>
                        <th>{{ __('claim_rates.status') }}</th>
                        <th>{{ __('common.action') }}</th>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('claim_rates.create')
@include('claim_rates.new_claim')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {

            // Translation variables for JavaScript
            const translations = {
                chooseDoctor: "{{ __('common.choose_doctor') }}",
                are_you_sure : "{{ __('common.are_you_sure') }}",
                yes_delete_it : "{{ __('common.yes_delete_it') }}",
            };
            let table = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/claim-rates/') }}",
                    data: function (d) {
                    }
                },
                dom: 'Bfrtip',
                buttons: {
                    buttons: [
                        // {extend: 'pdfHtml5', className: 'pdfButton'},
                        // {extend: 'excelHtml5', className: 'excelButton'},

                    ]
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'surname', name: 'surname'},
                    {data: 'othername', name: 'othername'},
                    {data: 'cash_rate', name: 'cash_rate'},
                    {data: 'insurance_rate', name: 'insurance_rate'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });

        function createRecord() {
            $("#rate-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#rate-modal').modal('show');
        }

        //filter doctor
        $('#doctor').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: translations.chooseDoctor,
            minimumInputLength: 2,
            ajax: {
                url: '/search-doctor',
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
        });


        function save_data() {
            //check save method
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
            $.ajax({
                type: 'POST',
                data: $('#rate-form').serialize(),
                url: "/claim-rates",
                success: function (data) {
                    $('#rate-modal').modal('hide');
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
           $.LoadingOverlay("show");
            $("#rate-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "claim-rates/" + id + "/edit",
                success: function (data) {
                    $('#id').val(id);
                    $('[name="insurance_rate"]').val(data.insurance_rate);
                    $('[name="cash_rate"]').val(data.cash_rate);
                    let doctor_data = {
                        id: data.doctor_id,
                        text: LanguageManager.joinName(data.surname, data.othername)
                    };
                    let newOption = new Option(doctor_data.text, doctor_data.id, true, true);
                    $('#doctor').append(newOption).trigger('change');
                   $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}');
                    $('#rate-modal').modal('show');

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
                data: $('#rate-form').serialize(),
                url: "/claim-rates/" + $('#id').val(),
                success: function (data) {
                    $('#rate-modal').modal('hide');
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
                    title: translations.are_you_sure,
                    text: "{{ __('claim_rates.delete_confirm_message') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: translations.yes_delete_it,
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
                        url: "/claim-rates/" + id,
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


        function newClaim(doctor_id, othername) {
            $("#new-claim-form")[0].reset();
            $('.renew_title').text(othername + " {{ __('claim_rates.new_claim_rate') }}");
            $('#doctor_id').val(doctor_id);
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#new-claim-modal').modal('show');
        }

        function save_new_rate() {
           $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('{{ __("common.processing") }}');
            $.ajax({
                type: 'POST',
                data: $('#new-claim-form').serialize(),
                url: "/claim-rates",
                success: function (data) {
                    $('#new-claim-modal').modal('hide');
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
                    $('#new-claim-modal').modal('show');

                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);
            if (status) {
                let oTable = $('#sample_1').dataTable();
                oTable.fnDraw(false);
            }
        }


    </script>
@endsection