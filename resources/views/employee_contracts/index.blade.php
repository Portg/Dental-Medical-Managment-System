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
                    <span class="caption-subject">{{ __('employee_contracts.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="#"
                                   onclick="createRecord()"> {{ __('common.add_new') }} <i
                                            class="fa fa-plus"></i> </a>
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
                       id="contracts-table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('employee_contracts.employee') }}</th>
                        <th>{{ __('employee_contracts.contract_type') }}</th>
                        <th>{{ __('employee_contracts.length') }}</th>
                        <th>{{ __('employee_contracts.start') }}</th>
                        <th>{{ __('employee_contracts.end') }}</th>
                        <th>{{ __('employee_contracts.payroll_type') }}</th>
                        <th>{{ __('employee_contracts.salary_commission') }}</th>
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
@include('employee_contracts.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {
            let table = $('#contracts-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/employee-contracts/') }}",
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
                    {data: 'employee', name: 'employee'},
                    {data: 'contract_type', name: 'contract_type'},
                    {data: 'contract_validity', name: 'contract_validity'},
                    {data: 'start_date', name: 'start_date'},
                    {data: 'contract_end_date', name: 'contract_end_date'},
                    {data: 'payroll_type', name: 'payroll_type'},
                    {data: 'amount', name: 'amount'},
                    // {data: 'status', name: 'status'},
                    // {data: 'addedBy', name: 'addedBy'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });


        $(document).ready(function () {


            $('.gross_section').hide();
            $('.commission_section').hide();
            $("input[type=radio][name=payroll_type]").on("change", function () {
                let action = $("input[type=radio][name=payroll_type]:checked").val();

                if (action == "Commission") {
                    $('[name="gross_salary"]').val(""); //reset the gross field
                    $('.commission_section').show();
                    $('.gross_section').hide();
                } else if (action == "Salary") {

                    $('[name="commission_percentage"]').val(""); //reset the commission field
                    $('.gross_section').show();
                    $('.commission_section').hide();
                }

            });
        });


        function createRecord() {
            $("#scale-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#scale-modal').modal('show');
        }

        //filter employee
        $('#employee').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('employee_contracts.choose_employee') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-employee',
                dataType: 'json',
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    console.log(data);
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
                data: $('#scale-form').serialize(),
                url: "/employee-contracts",
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
                    $('#btn-save').text('{{ __("common.save_changes") }}');
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
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "employee-contracts/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    let employee_data = {
                        id: data.employee_id,
                        text: data.surname + " " + data.othername
                    };
                    let newOption = new Option(employee_data.text, employee_data.id, true, true);
                    $('#employee').append(newOption).trigger('change');

                    $('input[name^="contract_type"][value="' + data.contract_type + '"').prop('checked', true);
                    $('[name="start_date"]').val(data.start_date);
                    $('[name="contract_length"]').val(data.contract_length);
                    $('input[name^="contract_period"][value="' + data.contract_period + '"').prop('checked', true);
                    $('input[name^="payroll_type"][value="' + data.payroll_type + '"').prop('checked', true);
                    if (data.payroll_type == "Commission") {
                        $('[name="commission_percentage"]').val(data.commission_percentage);
                        $('[name="gross_salary"]').val(""); //reset the gross field
                        $('.commission_section').show();
                        $('.gross_section').hide();
                    } else if (data.payroll_type == "Salary") {
                        $('[name="gross_salary"]').val(data.gross_salary);
                        $('[name="commission_percentage"]').val(""); //reset the commission field
                        $('.gross_section').show();
                        $('.commission_section').hide();
                    }

                    $.LoadingOverlay("hide");
                    $('#btn-save').text('{{ __("common.update_record") }}')
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
            $('#btn-save').text('{{ __("common.updating") }}');
            $.ajax({
                type: 'PUT',
                data: $('#scale-form').serialize(),
                url: "/employee-contracts/" + $('#id').val(),
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
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('employee_contracts.delete_confirm_message') }}",
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
                        url: "/employee-contracts/" + id,
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


        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);
            if (status) {
                setTimeout(function () {
                    location.reload();
                }, 1900);
            }
        }


    </script>
@endsection





