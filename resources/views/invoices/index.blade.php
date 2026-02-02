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
                    <span class="caption-subject"> {{ __('invoices.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group pull-right">
                                <a href="{{ url('export-invoices-report') }}" class="text-danger">
                                    <i class="icon-cloud-download"></i> {{ __('common.download_excel_report') }}  </a>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="col-md-12">
                    <form action="#" class="form-horizontal">
                        <div class="form-body">

                            <div class="row">
                                <div class="col-md-6">

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.period')}}</label>
                                        <div class="col-md-9">
                                            <select class="form-control" id="period_selector">
                                                <option>{{__('datetime.time_periods.all')}}</option>
                                                <option value="Today">{{__('datetime.time_periods.today')}}</option>
                                                <option value="Yesterday">{{__('datetime.time_periods.yesterday')}}</option>
                                                <option value="This week">{{__('datetime.time_periods.this_week')}}</option>
                                                <option value="Last week">{{__('datetime.time_periods.last_week')}}</option>
                                                <option value="This Month">{{__('datetime.time_periods.this_month')}}</option>
                                                <option value="Last Month">{{__('datetime.time_periods.last_month')}}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.date_range.start_date')}}</label>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control start_date"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('datetime.date_range.end_date')}}</label>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button type="button" id="customFilterBtn" class="btn purple-intense">{{ __('invoices.filter_invoices') }}
                                            </button>
                                            <button type="button" class="btn default">{{ __('common.clear') }}</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <br>
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="invoices-table">
                    <thead>
                    <tr>
                        <th>{{ __('invoices.hash') }}</th>
                        <th>{{ __('invoices.invoice_no') }}</th>
                        <th>{{ __('invoices.date') }}</th>
                        <th>{{ __('invoices.customer') }}</th>
                        <th>{{ __('invoices.total_amount') }}</th>
                        <th>{{ __('invoices.paid_amount') }}</th>
                        <th>{{ __('invoices.outstanding') }}</th>
                        <th>{{ __('invoices.added_by') }}</th>
                        <th>{{ __('common.actions') }}</th>
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
@include('invoices.payment.create')
@include('invoices.share_invoice')
@include('invoices.invoice_procedures')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        function default_todays_data() {
            // initially load today's date filtered data
            $('.start_date').val(todaysDate());
            $('.end_date').val(todaysDate());
            $("#period_selector").val('Today');
        }

        $('#period_selector').on('change', function () {
            switch (this.value) {
                case'Today':
                    $('.start_date').val(todaysDate());
                    $('.end_date').val(todaysDate());
                    break;
                case'Yesterday':
                    $('.start_date').val(YesterdaysDate());
                    $('.end_date').val(YesterdaysDate());
                    break;
                case'This week':
                    $('.start_date').val(thisWeek());
                    $('.end_date').val(todaysDate());
                    break;
                case'Last week':
                    lastWeek();
                    break;
                case'This Month':
                    $('.start_date').val(formatDate(thisMonth()));
                    $('.end_date').val(todaysDate());
                    break;
                case'Last Month':
                    lastMonth();
                    break;
            }
        });
        $(function () {
            // Load page-specific translations
            LanguageManager.loadAllFromPHP({
                'invoices': @json(__('invoices'))
            });
            default_todays_data();  //filter  date
            var table = $('#invoices-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/invoices/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.search = $('input[type="search"]').val();
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
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'invoice_no', name: 'invoice_no', orderable: false},
                    {data: 'created_at', name: 'created_at', orderable: false},
                    {data: 'customer', name: 'customer', orderable: false},
                    {data: 'amount', name: 'amount', orderable: false},
                    {data: 'paid_amount', name: 'paid_amount', orderable: false},
                    {data: 'due_amount', name: 'due_amount', orderable: false},
                    {data: 'addedBy', name: 'addedBy', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });
        $('#customFilterBtn').click(function () {
            $('#invoices-table').DataTable().draw(true);
        });

        function viewInvoiceProcedures(invoiceId){
            $('.noResultsText').hide();
            $.LoadingOverlay("show");
            $.ajax({
                type: 'get',
                url: "invoice-procedures/" + invoiceId,
                success: function (data) {
                    if (data.length !== 0) {
                        convertJsontoHtmlTable(data);
                    } else {
                        $('.noResultsText').show();
                    }
                    $.LoadingOverlay("hide");
                    $('#invoice-procedures-modal').modal('show')
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function convertJsontoHtmlTable(jsonResponseData) {

            //Getting value for table header
            // {'id', 'clinical_notes', 'treatment' , 'created_at'}
            var tablecolumns = [];
            for (var i = 0; i < jsonResponseData.length; i++) {
                for (var key in jsonResponseData[i]) {
                    if (tablecolumns.indexOf(key) === -1) {
                        tablecolumns.push(key);
                    }
                }
            }

            //Creating html table and adding class to it
            let invoiceProceduresTable = document.createElement("table");
            invoiceProceduresTable.classList.add("table");
            invoiceProceduresTable.classList.add("table-striped");
            invoiceProceduresTable.classList.add("table-bordered");
            invoiceProceduresTable.classList.add("table-hover")

            //Creating header of the HTML table using
            //tr
            let tr = invoiceProceduresTable.insertRow(-1);

            for (let i = 0; i < tablecolumns.length; i++) {
                //header
                var th = document.createElement("th");
                // 使用翻译函数处理列标题
                th.innerHTML = getTranslatedColumnName(tablecolumns[i]);
                tr.appendChild(th);
            }

            // Add jsonResponseData in table as tr or rows
            for (let i = 0; i < jsonResponseData.length; i++) {
                tr = invoiceProceduresTable.insertRow(-1);
                for (let j = 0; j < tablecolumns.length; j++) {
                    let tabCell = tr.insertCell(-1);
                    tabCell.innerHTML = jsonResponseData[i][tablecolumns[j]];
                }
            }

            //Final step , append html table to the container div
            let invoiceProceduresContainer = document.getElementById("invoiceProceduresContainer");
            invoiceProceduresContainer.innerHTML = "";
            invoiceProceduresContainer.appendChild(invoiceProceduresTable);
        }

        /**
         * 列名国际化函数
         * @param columnName
         * @returns {*}
         */
        function getTranslatedColumnName(columnName) {
            const translations = {
                'name': "{{ __('invoices.procedure') }}",
                'qty': "{{ __('invoices.quantity') }}",
                'price': "{{ __('invoices.unit_price') }}",
                'total': "{{ __('invoices.total_amount') }}"
            };
            return translations[columnName] || columnName;
        }

        function print_invoice() {
            window.print();
        }

        $(document).ready(function () {
            //hide the insurance companies
            $('#company').val([]).trigger('change');
            $("#company").select2("val", "");
            $('.insurance_company').hide();

            //hide self account
            $('#self_account_id').val([]).trigger('change');
            $("#self_account_id").select2("val", "");
            $('.self_account').hide();

            //hide the cheque payment fields
            $('#cheque_payment').hide();
            $('[name="cheque_no"]').val("");
            $('[name="account_name"]').val("");
            $('[name="bank_name"]').val("");

            $("input[type=radio][name=payment_method]").on("change", function () {
                let action = $("input[type=radio][name=payment_method]:checked").val();

                if (action === "Self Account") {
                    //show the select
                    $('.self_account').show();
                    $('#self_account_id').next(".select2-container").show();
                    //now  hide insurance view
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();
                    //change the value back to default
                    $('#company').val([]).trigger('change');

                    //hide the cheque payment fields
                    $('#cheque_payment').hide();
                    $('[name="cheque_no"]').val("");
                    $('[name="account_name"]').val("");
                    $('[name="bank_name"]').val("");

                } else if (action === "Insurance") {
                    //show the select
                    $('.insurance_company').show();
                    $('#company').next(".select2-container").show();
                    //hide  self account
                    $('.self_account').hide();
                    $('#self_account_id').next(".select2-container").hide();
                    //change the value back to default
                    $('#self_account_id').val([]).trigger('change');

                    //hide the cheque payment fields
                    $('#cheque_payment').hide();
                    $('[name="cheque_no"]').val("");
                    $('[name="account_name"]').val("");
                    $('[name="bank_name"]').val("");

                } else if (action === "Cheque") {
                    //show the cheque payment fields
                    $('#cheque_payment').show();

                    //change the value back to default
                    $('#company').val([]).trigger('change');
                    //now  hide insurance view
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();

                    //hide also self account
                    $('.self_account').hide();
                    $('#self_account_id').next(".select2-container").hide();
                    $('#self_account_id').val([]).trigger('change');

                } else {
                    //change the value back to default
                    $('#company').val([]).trigger('change');
                    //now  hide insurance view
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();

                    //hide also self account
                    $('.self_account').hide();
                    $('#self_account_id').next(".select2-container").hide();
                    $('#self_account_id').val([]).trigger('change');

                    //hide the cheque payment fields
                    $('#cheque_payment').hide();
                    $('[name="cheque_no"]').val("");
                    $('[name="account_name"]').val("");
                    $('[name="bank_name"]').val("");
                }

            });

            ///


        });

        //filter self accounts
        $('#self_account_id').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('invoices.choose_self_account') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-self-account',
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

        //filter insurance companies
        $('#company').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('invoices.choose_insurance_company') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-insurance-company',
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

        function record_payment(id) {
            $.LoadingOverlay("show");
            $("#payment-form")[0].reset();
            $('#invoice_id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_changes") }}');
            $.ajax({
                type: 'get',
                url: "invoice-amount/" + id,
                success: function (data) {
                    console.log(data);
                    $('#invoice_id').val(id);

                    $('[name="amount"]').val(data.amount);
                    $('[name="payment_date"]').val(data.today_date);

                    if (data.patient != null) {
                        let company_data = {
                            id: data.patient.insurance_company_id,
                            text: data.patient.name
                        };
                        let newOption = new Option(company_data.text, company_data.id, true, true);
                        $('#company').append(newOption).trigger('change');

                    }

                    $.LoadingOverlay("hide");
                    $('#payment-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }


        function save_payment_record() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('{{ __("common.processing") }}');
            $.ajax({
                type: 'POST',
                data: $('#payment-form').serialize(),
                url: "/payments",
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

        function shareInvoiceView(invoice_id) {
            $.LoadingOverlay("show");
            $("#share-invoice-form")[0].reset();
            $('#btn-share').attr('disabled', false);
            $('#btn-share').text('{{ __("invoices.share_invoice") }}');
            $.ajax({
                type: 'GET',
                url: "/share-invoice-details/" + invoice_id,
                success: function (data) {
                    console.log(data)
                    $.LoadingOverlay("hide");
                    $('[name="invoice_id"]').val(data.id);
                    $('[name="invoice_no"]').val(data.invoice_no);
                    $('[name="name"]').val(LanguageManager.joinName(data.surname, data.othername));
                    $('[name="email"]').val(data.email);
                    $('#share-invoice-modal').modal('show');

                },
                error: function (xhr, status, error) {
                    alert(error);
                }
            });


        }

        function sendInvoice() {
            $.LoadingOverlay("show");
            $('#btn-share').attr('disabled', true);
            $('#btn-share').text('{{ __("common.processing") }}');
            $.ajax({
                type: 'POST',
                data: $('#share-invoice-form').serialize(),
                url: "/share-invoice",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    $('#share-invoice-modal').modal('hide');
                    alert_dialog(data.message, "success");
                },
                error: function (xhr, status, error) {
                    $('#btn-share').attr('disabled', false);
                    $('#btn-share').text('{{ __("invoices.share_invoice") }}');
                    $('#share-invoice-modal').modal('show');
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });

        }


        function deleteInvoice(id) {
            swal({
                    title: "{{ __('common.are_you_sure') }}",
                    text: "{{ __('invoices.confirm_delete_invoice') }}",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "{{ __('common.yes_delete_it') }}",
                    cancelButtonText: "{{ __('common.cancel') }}",
                    closeOnConfirm: false
                },
                function () {

                    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                    $.LoadingOverlay("show");
                    $.ajax({
                        type: 'DELETE',
                        data: {
                            _token: CSRF_TOKEN
                        },
                        url: "invoices/" + id,
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
                            alert_dialog("{{ __('messages.error_occurred') }}", "danger");
                        }
                    });

                });

        }

        function alert_dialog(message, status) {
            swal("{{ __('common.alert') }}", message, status);
            if (status) {
                let oTable = $('#invoices-table').dataTable();
                oTable.fnDraw(false);
            }
        }

    </script>
@endsection





