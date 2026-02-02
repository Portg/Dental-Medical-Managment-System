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
                    <span class="caption-subject"> {{ __('payslips.payroll_management') }}/ {{ __('payslips.page_title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('payslips.add_new') }}</button>
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
                       id="payslips-table">
                    <thead>
                    <tr>
                        <th>{{ __('payslips.id') }}</th>
                        <th>{{ __('payslips.employee') }}</th>
                        <th>{{ __('payslips.month') }}</th>
                        <th>{{ __('payslips.gross_commission') }}</th>
                        <th>{{ __('payslips.allowance') }}</th>
                        <th>{{ __('payslips.deductions') }}</th>
                        <th>{{ __('payslips.paid') }}</th>
                        <th>{{ __('payslips.outstanding') }}</th>
                        <th>{{ __('payslips.added_by') }}</th>
                        <th>{{ __('payslips.action') }}</th>
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
    <span>{{ __('payslips.loading') }}</span>
</div>
@include('payslips.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'payslips': @json(__('payslips'))
            });

            let table = $('#payslips-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/payslips/') }}",
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
                    {data: 'payslip_month', name: 'payslip_month'},
                    {data: 'basic_salary', name: 'basic_salary'},
                    {data: 'total_allowances', name: 'total_allowances'},
                    {data: 'total_deductions', name: 'total_deductions'},
                    {data: 'total_advances', name: 'total_advances'},
                    {data: 'due_balance', name: 'due_balance'},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });

        function createRecord() {
            $("#scale-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $('#scale-modal').modal('show');
        }

        $(document).on('click', '.remove-tr', function () {

            $(this).parents('tr').remove();

        });


        let i = 0;
        $("#add_allowance").click(function () {
            ++i;

            $("#AllowancesTable").append(
                '<tr>' +
                '<td>  <select class="form-control" name="addAllowance[' + i + '][allowance]">\n' +
                '                                        <option value="House Rent Allowance">{{ __("allowances.house_rent") }}</option>\n' +
                '                                        <option value="Medical Allowance">{{ __("allowances.medical") }}</option>\n' +
                '                                        <option value="Bonus">{{ __("allowances.bonus") }}</option>\n' +
                '                                        <option value="Dearness Allowance">{{ __("allowances.dearness") }}</option>\n' +
                '                                        <option value="Travelling Allowance">{{ __("allowances.travelling") }}</option>\n' +
                '                                        <option value="Overtime Allowance">{{ __("allowances.overtime") }}</option>\n' +
                '                                    </select></td>' +
                '<td> <input type="number"  name="addAllowance[' + i + '][allowance_amount]" placeholder="{{ __("common.enter_amount") }}" class="form-control"/></td>' +
                '<td><button type="button" class="btn btn-danger remove-tr">{{ __("common.remove") }}</button></td>' +
                '</tr>');
        });


        let x = 0;
        $("#add_deduction").click(function () {
            ++x;
            $("#DeductionsTable").append(
                '<tr>' +
                '<td>  <select class="form-control" name="addDeduction[' + x + '][deduction]">\n' +
                ' <option value="Loan">{{ __("deductions.loan") }}</option>\n' +
                ' <option value="Tax">{{ __("deductions.tax") }}</option>' +
                '</select></td>' +
                '<td> <input type="number"  name="addDeduction[' + x + '][deduction_amount]" placeholder="{{ __("common.enter_amount") }}" class="form-control"/></td>' +
                '<td><button type="button" class="btn btn-danger remove-tr">{{ __("common.remove") }}</button></td>' +
                '</tr>');
        });


        //filter employee
        $('#employee').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('payslips.choose_employee') }}",
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

        $(document).ready(function () {
            //first hide the allowances and deductions fields
            $('#AllowancesTable').hide();
            $('#DeductionsTable').hide();
            //now first handle allowances
            $("input[type=radio][name=allowances_include]").on("change", function () {
                let action = $("input[type=radio][name=allowances_include]:checked").val();

                if (action == "No") {
                    //now hide the view
                    $('#AllowancesTable').hide();
                } else {
                    //show allowances table fields
                    $('#AllowancesTable').show();
                }
            });

            //handle deductions
            $("input[type=radio][name=deductions_include]").on("change", function () {
                let action = $("input[type=radio][name=deductions_include]:checked").val();

                if (action == "No") {
                    //now hide the view
                    $('#DeductionsTable').hide();
                } else {
                    //show allowances table fields
                    $('#DeductionsTable').show();
                }
            });


        });


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
                data: $('#scale-form').serialize(),
                url: "/payslips",
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
                url: "payslips/" + id + "/edit",
                success: function (data) {
                    console.log(data);
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
                url: "/payslips/" + $('#id').val(),
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
                    text: "{{ __('payslips.delete_confirm_message') }}",
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
                        url: "/payslips/" + id,
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
                let oTable = $('#payslips-table').dataTable();
                oTable.fnDraw(false);
            }
        }


    </script>
@endsection





