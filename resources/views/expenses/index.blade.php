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
                    <span class="caption-subject">{{ __('expenses.title') }}</span>
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
                        <div class="col-md-6">
                            <div class="btn-group pull-right">
                                <a href="{{ url('export-expenses') }}" class="text-danger">
                                    <i class="icon-cloud-download"></i> {{ __('common.download_excel_report') }}
                                </a>
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
                                            <button type="button" id="customFilterBtn" class="btn purple-intense">{{__('expenses.filter_expenses')}}
                                            </button>
                                            <button type="button" class="btn default">{{__('common.clear')}}</button>
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
                       id="expenses-table">
                    <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        {{--                        <th>Purchase No</th>--}}
                        <th>{{__('expenses.purchase_date')}}</th>
                        <th>{{__('expenses.supplier_name')}}</th>
                        <th>{{__('expenses.total_amount')}}</th>
                        <th>{{__('expenses.paid_amount')}}</th>
                        <th>{{__('expenses.outstanding')}}</th>
                        <th>{{__('expenses.added_by')}}</th>
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
    <span>{{__('common.loading')}}</span>
</div>
@include('expenses.create')
@include('expenses.payment.create')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        // Load page-specific translations
        LanguageManager.loadAllFromPHP({
            'expenses': @json(__('expenses'))
        });

        // Translation variables for JavaScript
        const translations = {
            itemPlaceHolder: "{{ __('expenses.enter_item') }}",
            descriptionPlaceHolder: "{{ __('expenses.enter_description') }}",
            qtyPlaceHolder: "{{ __('expenses.enter_quantity') }}",
            unitPricePlaceHolder: "{{ __('expenses.enter_unit_price') }}",
            totalAmountPlaceHolder: "{{ __('expenses.enter_total_amount') }}",
            removeBtn: "{{ __('common.remove') }}",
            // 新增缺失的翻译
            chooseExpenseCategory: "{{ __('expenses.choose_expense_category') }}",
            processing: "{{ __('common.processing') }}",
            saveRecord: "{{ __('common.save_record') }}",
            savePurchase: "{{ __('expenses.save_purchase') }}",
            // swal对话框
            confirmDelete: "{{ __('common.confirm_delete') }}",
            deleteWarning: "{{ __('expenses.delete_warning') }}",
            yesDelete: "{{ __('common.yes_delete') }}"
        };

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


        let suppliers_ary = [];
        let expense_categories_arry = [];
        $(function () {
            default_todays_data();  //filter  data
            var table = $('#expenses-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/expenses/') }}",
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
                    // {data: 'purchase_no', name: 'purchase_no'},
                    {data: 'purchase_date', name: 'purchase_date'},
                    {data: 'supplier_name', name: 'supplier_name'},
                    {data: 'amount', name: 'amount'},
                    {data: 'paid_amount', name: 'paid_amount'},
                    {data: 'due_amount', name: 'due_amount'},
                    {data: 'added_by', name: 'added_by'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });
        $('#customFilterBtn').click(function () {
            $('#expenses-table').DataTable().draw(true);
        });


        $(document).ready(function () {
            $.ajax({
                type: 'get',
                url: "/filter-suppliers",
                success: function (data) {
                    suppliers_ary = JSON.parse(data);
                }
            }).done(function () {

                $("#supplier").typeahead({
                    source: suppliers_ary,
                    minLength: 1
                });
            });

            //get expense items array
            $.ajax({
                type: 'get',
                url: "/expense-categories-array",
                success: function (data) {
                    expense_categories_arry = JSON.parse(data);
                }
            }).done(function () {

                $("#item").typeahead({
                    source: expense_categories_arry,
                    minLength: 1
                });
            });

        });

        function createRecord() {
            $("#purchase-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('[name="purchase_date"]').val(todaysDate());
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text('{{ __("common.save_record") }}');
            $('#purchase-modal').modal('show');
        }

        $(document).on('click', '.remove-tr', function () {

            $(this).parents('tr').remove();

        });

        const expenseOptions = `
            <option value="">{{ __("expenses.choose_expense_category") }}</option>
            @foreach ($chart_of_accts as $item_cat)
            <option value="{{ $item_cat->id }}">{{ $item_cat->name }}</option>
            @endforeach
        `;

        let i = 0;
        $("#add").click(function () {
            ++i;

            let value = '<select id="select2-single-input-group-sm" class="form-control select2" name="addmore[' + i + '][expense_category]">' +
                expenseOptions +
                '</select>';

            $("#purchasesTable").append(
                '<tr>' +
                '<td><input type="text" id="item_append' + i + '" name="addmore[' + i + '][item]" placeholder="' + translations.itemPlaceHolder + '" class="form-control"/></td>' +
                '<td><input type="text" id="description' + i + '" name="addmore[' + i + '][description]" placeholder="' + translations.descriptionPlaceHolder + '" class="form-control"/></td>' +
                '<td>' + value + '</td>' +
                '<td><input type="number" id="qty' + i + '" name="addmore[' + i + '][qty]" placeholder="' + translations.qtyPlaceHolder + '" class="form-control"/></td>' +
                '<td><input type="number" id="price-single-unit' + i + '" name="addmore[' + i + '][price]" placeholder="' + translations.unitPricePlaceHolder + '" class="form-control"/></td>' +
                '<td><input type="text" id="total_amount' + i + '" readonly placeholder="' + translations.totalAmountPlaceHolder + '" class="form-control"/></td>' +
                '<td><button type="button" class="btn btn-danger remove-tr">' + translations.removeBtn + '</button></td>' +
                '</tr>'
            );

            //also allow auto complete of the search of the expense items category
            $("#item_append" + i).typeahead({
                source: expense_categories_arry,
                minLength: 1
            });
            let populated_categories = $('.expense_categories')[0].innerHTML;
            let select = '  <select id="select2-single-input-group-sm"\n' +
                ' class="form-control select2"name="addmore[' + i + '][expense_category]">' + populated_categories + '</select>';
            //append expense_categories_append
            // $('#expense_categories_append' + i).append(populated_categories);

            // $('#expense_categories_append' + i).append(value[0].innerHTML);
            // console.log()

            {{--var a = [@foreach($data as $k => $info)--}}
            // {{--    '{{ $info }}',--}}
            {{--    @endforeach ]--}}



            //change the name of the select
            // .setAttribute('name', 'horse');


            //work on the qty,price and total amount
            $('#qty' + i).on('keyup change', function () {
                if ($(this).val() && $('#price-single-unit' + i).val()) {
                    $('#total_amount' + i).val(structureMoney("" + $(this).val() * ($('#price-single-unit' + i).val().replace(/,/g, ""))))

                } else if (!$(this).val()) {
                    $('#total_amount' + i).val("")
                }

            });

            $('#price-single-unit' + i).on('keyup change', function () {
                if ($(this).val() && $('#qty' + i).val()) {
                    $('#total_amount' + i).val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#qty' + i).val()))
                } else if (!$(this).val()) {
                    $('#total_amount' + i).val("")
                }
            });

        });

        $(document).ready(function () {

            $('#qty').on('keyup change', function () {
                if ($(this).val() && $('#price-single-unit').val()) {
                    $('#total_amount').val(structureMoney("" + $(this).val() * ($('#price-single-unit').val().replace(/,/g, ""))))
                    console.log($('#total_amount').val())
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }

            });

            $('#price-single-unit').on('keyup change', function () {
                if ($(this).val() && $('#qty').val()) {
                    $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#qty').val()))
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }
            });

        });

        function structureMoney(value) {
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }


        function save_purchase() {
            $.LoadingOverlay("show");
            $('#btn-save').attr('disabled', true);
            $('#btn-save').text('translations.processing');
            $.ajax({
                type: 'POST',
                data: $('#purchase-form').serialize(),
                url: "/expenses",
                success: function (data) {
                    $('#purchase-modal').modal('hide');
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
                    $('#btn-save').text('{{ __("expenses.save_purchase") }}');
                    $('#purchase-modal').modal('show');
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
                    title: LanguageManager.trans('messages.are_you_sure', "{{ __('messages.are_you_sure') }}"),
                    text: LanguageManager.trans('messages.cannot_recover_expense', "{{ __('messages.cannot_recover_expense') }}"),
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: LanguageManager.trans('common.yes_delete_it', "{{ __('common.yes_delete_it') }}"),
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
                        url: "/expenses/" + id,
                        success: function (data) {
                            console.log(data.message);
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

        function RecordPayment(expense_id) {
            $.LoadingOverlay("show");
            $("#payment-form")[0].reset();
            $('#expense_id').val(''); ///always reset hidden form fields
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text('{{ __("common.save_record") }}');

            $.ajax({
                type: 'get',
                url: "purchase-balance/" + expense_id,
                success: function (data) {
                    console.log(data);
                    $('#expense_id').val(expense_id);
                    $('[name="amount"]').val(data.amount);
                    $('[name="payment_date"]').val(data.today_date);

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
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text('{{ __("common.processing") }}');
            $.ajax({
                type: 'POST',
                data: $('#payment-form').serialize(),
                url: "/expense-payments",
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
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text('{{ __("common.save_record") }}');
                    $('#payment-modal').modal('show');

                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function alert_dialog(message, status) {
            swal(LanguageManager.trans('common.alert', "{{ __('common.alert') }}"), message, status);
            if (status) {
                let oTable = $('#expenses-table').dataTable();
                oTable.fnDraw(false);
            }
        }


    </script>
@endsection





