@extends('layouts.list-page')

@section('page_title', __('quotations.billing_quotations'))
@section('table_id', 'quotations-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('quotations.add_new') }}</button>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('quotations.quotation_no') }}</div>
            <input type="text" class="form-control" placeholder="{{ __('quotations.enter_quotation_no') }}" id="quotation_no">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('quotations.period') }}</div>
            <select class="form-control" id="period_selector">
                <option value="">{{ __('quotations.all') }}</option>
                <option value="Today">{{ __('quotations.today') }}</option>
                <option value="Yesterday">{{ __('quotations.yesterday') }}</option>
                <option value="This week">{{ __('quotations.this_week') }}</option>
                <option value="Last week">{{ __('quotations.last_week') }}</option>
                <option value="This Month">{{ __('quotations.this_month') }}</option>
                <option value="Last Month">{{ __('quotations.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <div class="filter-label">{{ __('quotations.start_date') }}</div>
            <input type="text" class="form-control start_date" id="filter_start_date">
        </div>
        <div class="col-md-2">
            <div class="filter-label">{{ __('quotations.end_date') }}</div>
            <input type="text" class="form-control end_date" id="filter_end_date">
        </div>
        <div class="col-md-2 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('quotations.hash') }}</th>
    <th>{{ __('quotations.quotation_no') }}</th>
    <th>{{ __('quotations.date') }}</th>
    <th>{{ __('quotations.customer') }}</th>
    <th>{{ __('quotations.total_amount') }}</th>
    <th>{{ __('quotations.added_by') }}</th>
    <th>{{ __('quotations.actions') }}</th>
@endsection

@section('modals')
    @include('quotations.create')
    @include('quotations.share_quotation')
@endsection

@section('page_js')
    <script type="text/javascript">
        function default_todays_data() {
            // initially load today's date filtered data
            $('#filter_start_date').val(todaysDate());
            $('#filter_end_date').val(todaysDate());
            $("#period_selector").val('Today');
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

        $(function () {
            // Load page-specific translations
            LanguageManager.loadAllFromPHP({
                'quotations': @json(__('quotations'))
            });

            default_todays_data();  //filter  date
            dataTable = $('#quotations-table').DataTable({
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/quotations/') }}",
                    data: function (d) {
                        d.start_date = $('#filter_start_date').val();
                        d.end_date = $('#filter_end_date').val();
                        d.quotation_no = $('#quotation_no').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'quotation_no', name: 'quotation_no', orderable: false},
                    {data: 'created_at', name: 'created_at', orderable: false},
                    {data: 'customer', name: 'customer', orderable: false},
                    {data: 'amount', name: 'amount', orderable: false},
                    {data: 'addedBy', name: 'addedBy', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        function clearCustomFilters() {
            $('#quotation_no').val('');
            $('#period_selector').val('');
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
        }

        function createRecord() {
            $("#quotation-form")[0].reset();
            $('#quotation-modal').modal('show');
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text('{{ __("common.save_changes") }}');
        }


        //filter patients
        $('#patient').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('common.choose_patient') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-patient',
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


        let i = 0;
        $("#addQuotationItem").click(function () {
            ++i;

            // 关键：用 let 保存当前索引，避免闭包问题
            let currentIndex = i;

            $("#QuotationItemsTable").append(
                '<tr>' +
                '<td>' +
                '<select id="service_append' + currentIndex + '" name="addmore[' + currentIndex + '][medical_service_id]" class="form-control" style="width: 100%;"></select>' +
                '</td>' +
                '<td>' +
                '<input type="text" name="addmore[' + currentIndex + '][tooth_no]" placeholder="{{ __("common.enter_tooth_no") }}" class="form-control"/>' +
                '</td>' +
                '<td>' +
                '<input type="number" onkeyup="QTYKeyChange(' + currentIndex + ')" id="procedure_qty' + currentIndex + '" name="addmore[' + currentIndex + '][qty]" placeholder="{{ __("common.enter_qty") }}" class="form-control"/>' +
                '</td>' +
                '<td>' +
                '<input type="number" onkeyup="PriceKeyChange(' + currentIndex + ')" id="procedure_price' + currentIndex + '" name="addmore[' + currentIndex + '][price]" placeholder="{{ __("common.enter_price") }}" class="form-control"/>' +
                '</td>' +
                '<td>' +
                '<input type="text" readonly id="total_amount' + currentIndex + '" class="form-control"/>' +
                '</td>' +
                '<td>' +
                '<button type="button" class="btn btn-danger remove-tr">{{ __("common.remove") }}</button>' +
                '</td>' +
                '</tr>'
            );

            // 初始化 select2
            $('#service_append' + currentIndex).select2({
                language: '{{ app()->getLocale() }}',
                placeholder: "{{ __('common.select_procedure') }}",
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '/search-medical-service',
                    dataType: 'json',
                    delay: 250,
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
            }).on("select2:select", function (e) {
                // 使用 currentIndex 而不是 i
                let price = e.params.data.price;

                if (price && price !== "" && price !== 0) {
                    $('#procedure_price' + currentIndex).val(price);
                    $('#procedure_qty' + currentIndex).val(1);
                    calculateRowTotal(currentIndex);
                } else {
                    $('#procedure_price' + currentIndex).val('');
                    $('#procedure_qty' + currentIndex).val('');
                    $('#total_amount' + currentIndex).val('');
                }
            });
        });

        // 计算单行总额
        function calculateRowTotal(index) {
            let qty = parseFloat($('#procedure_qty' + index).val()) || 0;
            let price = parseFloat($('#procedure_price' + index).val().toString().replace(/,/g, "")) || 0;
            let amount = qty * price;

            if (amount > 0) {
                $('#total_amount' + index).val(structureMoney(amount.toString()));
            } else {
                $('#total_amount' + index).val('');
            }
        }
        $(document).on('click', '.remove-tr', function () {

            $(this).parents('tr').remove();

        });

        //filter Procedures
        $('#service').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('common.select_procedure') }}",
            minimumInputLength: 2,
            ajax: {
                url: '/search-medical-service',
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
        }).on("select2:select", function (e) {
            let price = e.params.data.price;
            if (price != "" || price != 0) {
                $('#procedure_qty').val(1)
                $('#procedure_price').val(price);
                let amount = ($('#procedure_price').val().replace(/,/g, "")) * $('#procedure_qty').val();
                $('#total_amount').val(structureMoney("" + amount));
            } else {
                $('#procedure_price').val('');
            }

        });


        $(document).ready(function () {

            $('#procedure_qty').on('keyup change', function () {
                if ($(this).val() && $('#procedure_price').val()) {
                    $('#total_amount').val(structureMoney("" + $(this).val() * ($('#procedure_price').val().replace(/,/g, ""))))
                    console.log($('#total_amount').val())
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }

            });

            $('#procedure_price').on('keyup change', function () {
                if ($(this).val() && $('#procedure_qty').val()) {
                    $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#procedure_qty').val()))
                } else if (!$(this).val()) {
                    $('#total_amount').val("")
                }
            });
        });


        function QTYKeyChange(position) {
            if ($('#procedure_qty' + position).val() && $('#procedure_price' + position).val()) {
                $('#total_amount' + position).val(structureMoney("" + $('#procedure_qty' + position).val() * ($('#procedure_price' + position).val().replace(/,/g, ""))))
            } else if (!$('#procedure_qty' + position).val()) {
                $('#total_amount' + position).val("")
            }
        }

        function PriceKeyChange(position) {
            if ($('#procedure_price' + position).val() && $('#procedure_qty' + position).val()) {
                $('#total_amount' + position).val(structureMoney("" + $('#procedure_price' + position).val() * ($('#procedure_qty' + position).val().replace(/,/g, ""))))
            } else if (!$('#procedure_price' + position).val()) {
                $('#total_amount' + position).val("")
            }
        }


        function save_quotation() {
            $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text('{{ __('messages.processing') }}');
            $.ajax({
                type: 'POST',
                data: $('#quotation-form').serialize(),
                url: "/quotations",
                success: function (data) {
                    $('#quotation-modal').modal('hide');
                    $.LoadingOverlay("hide");
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#quotation-modal').modal('show');
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text('{{ __('quotations.generate_quotation') }}');

                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }


        function shareQuotationView(quotation_id) {
            $.LoadingOverlay("show");
            $("#share-quotation-form")[0].reset();
            $('#btn-share').attr('disabled', false);
            $('#btn-share').text('{{ __('quotations.share_quotation') }}');
            $.ajax({
                type: 'GET',
                url: "/share-quotation-details/" + quotation_id,
                success: function (data) {
                    console.log(data)
                    $.LoadingOverlay("hide");
                    $('[name="quotation_id"]').val(data.id);
                    $('[name="quotation_no"]').val(data.quotation_no);
                    $('[name="name"]').val(LanguageManager.joinName(data.surname, data.othername));
                    $('[name="email"]').val(data.email);
                    $('#share-quotation-modal').modal('show');


                },
                error: function (xhr, status, error) {
                    alert(error);
                }
            });


        }

        function sendQuotation() {
            $.LoadingOverlay("show");
            $('#btn-share').attr('disabled', true);
            $('#btn-share').text('{{ __('messages.processing') }}');
            $.ajax({
                type: 'POST',
                data: $('#share-quotation-form').serialize(),
                url: "/share-quotation",
                success: function (data) {
                    $.LoadingOverlay("hide");
                    $('#share-quotation-modal').modal('hide');
                    alert_dialog(data.message, "success");
                },
                error: function (xhr, status, error) {
                    $('#btn-share').attr('disabled', false);
                    $('#btn-share').text('{{ __('quotations.share_quotation') }}');
                    $('#share-quotation-modal').modal('show');
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });

        }


        function structureMoney(value) {
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }


    </script>
@endsection
