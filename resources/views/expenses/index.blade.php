@extends('layouts.list-page')

@section('page_title', __('expenses.title'))
@section('table_id', 'expenses-table')

@section('header_actions')
    <a href="{{ url('export-expenses') }}" class="btn btn-default">
        <i class="icon-cloud-download"></i> {{ __('common.download_excel_report') }}
    </a>
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.period') }}</div>
            <select class="form-control" id="period_selector">
                <option value="">{{ __('datetime.time_periods.all') }}</option>
                <option value="Today">{{ __('datetime.time_periods.today') }}</option>
                <option value="Yesterday">{{ __('datetime.time_periods.yesterday') }}</option>
                <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
                <option value="Last week">{{ __('datetime.time_periods.last_week') }}</option>
                <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
                <option value="Last Month">{{ __('datetime.time_periods.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.date_range.start_date') }}</div>
            <input type="text" class="form-control start_date" id="filter_start_date">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('datetime.date_range.end_date') }}</div>
            <input type="text" class="form-control end_date" id="filter_end_date">
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('expenses.purchase_date') }}</th>
    <th>{{ __('expenses.supplier_name') }}</th>
    <th>{{ __('expenses.total_amount') }}</th>
    <th>{{ __('expenses.paid_amount') }}</th>
    <th>{{ __('expenses.outstanding') }}</th>
    <th>{{ __('expenses.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('expenses.create')
    @include('expenses.payment.create')
@endsection

@section('page_js')
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
            chooseExpenseCategory: "{{ __('expenses.choose_expense_category') }}",
            processing: "{{ __('common.processing') }}",
            saveRecord: "{{ __('common.save_record') }}",
            savePurchase: "{{ __('expenses.save_purchase') }}",
            confirmDelete: "{{ __('common.confirm_delete') }}",
            deleteWarning: "{{ __('expenses.delete_warning') }}",
            yesDelete: "{{ __('common.yes_delete') }}"
        };

        function default_todays_data() {
            // initially load today's date filtered data
            $('#filter_start_date').val(todaysDate());
            $('#filter_end_date').val(todaysDate());
            $("#period_selector").val('Today');
        }

        function clearCustomFilters() {
            $('#period_selector').val('');
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
        }

        $('#period_selector').on('change', function () {
            switch (this.value) {
                case'Today':
                    $('#filter_start_date').val(todaysDate());
                    $('#filter_end_date').val(todaysDate());
                    break;
                case'Yesterday':
                    $('#filter_start_date').val(YesterdaysDate());
                    $('#filter_end_date').val(YesterdaysDate());
                    break;
                case'This week':
                    $('#filter_start_date').val(thisWeek());
                    $('#filter_end_date').val(todaysDate());
                    break;
                case'Last week':
                    lastWeek();
                    break;
                case'This Month':
                    $('#filter_start_date').val(formatDate(thisMonth()));
                    $('#filter_end_date').val(todaysDate());
                    break;
                case'Last Month':
                    lastMonth();
                    break;
            }
            doSearch();
        });


        let suppliers_ary = [];
        let expense_categories_arry = [];
        $(function () {
            default_todays_data();  //filter  data
            dataTable = $('#expenses-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/expenses/') }}",
                    data: function (d) {
                        d.start_date = $('#filter_start_date').val();
                        d.end_date = $('#filter_end_date').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'purchase_date', name: 'purchase_date'},
                    {data: 'supplier_name', name: 'supplier_name'},
                    {data: 'amount', name: 'amount'},
                    {data: 'paid_amount', name: 'paid_amount'},
                    {data: 'due_amount', name: 'due_amount'},
                    {data: 'added_by', name: 'added_by'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();

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
            $('#btn-save').text(translations.processing);
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


    </script>
@endsection
