@extends('layouts.list-page')

@section('page_title', __('online_bookings.appointments_online_bookings'))
@section('table_id', 'bookings-table')

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.period') }}</div>
            <select class="form-control" id="period_selector">
                <option>{{ __('online_bookings.all') }}</option>
                <option value="Today">{{ __('online_bookings.today') }}</option>
                <option value="Yesterday">{{ __('online_bookings.yesterday') }}</option>
                <option value="This week">{{ __('online_bookings.this_week') }}</option>
                <option value="Last week">{{ __('online_bookings.last_week') }}</option>
                <option value="This Month">{{ __('online_bookings.this_month') }}</option>
                <option value="Last Month">{{ __('online_bookings.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.start_date') }}</div>
            <input type="text" class="form-control start_date" placeholder="{{ __('online_bookings.start_date') }}">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.end_date') }}</div>
            <input type="text" class="form-control end_date" placeholder="{{ __('online_bookings.end_date') }}">
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('online_bookings.clear') }}</button>
            <button type="button" id="customFilterBtn" class="btn btn-primary">{{ __('online_bookings.filter_bookings') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('online_bookings.id') }}</th>
    <th>{{ __('online_bookings.booking_date') }}</th>
    <th>{{ __('online_bookings.patient') }}</th>
    <th>{{ __('online_bookings.phone_no') }}</th>
    <th>{{ __('online_bookings.email') }}</th>
    <th>{{ __('online_bookings.preferred_appointment_date') }}</th>
    <th>{{ __('online_bookings.preferred_appointment_time') }}</th>
    <th>{{ __('online_bookings.is_new_patient') }}</th>
    <th>{{ __('online_bookings.status') }}</th>
    <th>{{ __('online_bookings.action') }}</th>
@endsection

@section('modals')
    @include('online_bookings.preview_booking')
@endsection

@section('page_js')
    <script type="text/javascript">

        function default_todays_data() {
            // initially load today's date filtered data
            $('.start_date').val(formatDate(thisMonth()));
            $('.end_date').val(todaysDate());
            $("#period_selector").val('This Month');
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
            LanguageManager.loadAllFromPHP({
                'online_bookings': @json(__('online_bookings'))
            });

            default_todays_data();  //filter  date
            dataTable = $('#bookings-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/online-bookings/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.search = $('input[type="search"]').val();
                    }
                },
                dom: 'rtip',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                    {data: 'booking_date', name: 'booking_date'},
                    {data: 'full_name', name: 'full_name'},
                    {data: 'phone_no', name: 'phone_no'},
                    {data: 'email', name: 'email'},
                    {data: 'start_date', name: 'start_date'},
                    {data: 'start_time', name: 'start_time'},
                    {data: 'visit_history', name: 'visit_history'},
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            setupEmptyStateHandler();
        });

        $('#customFilterBtn').click(function () {
            dataTable.draw(true);
        });


        //filter insurance companies
        $('#company').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('online_bookings.choose_insurance_company') }}",
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


        //filter doctor
        $('#doctor').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('online_bookings.choose_doctor') }}",
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
                    console.log(data);
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });


        function ViewMessage(id) {
            $.LoadingOverlay("show");
            $('#id').val(''); ///always reset hidden form fields
            $('#btn-save').attr('disabled', false);
            $.ajax({
                type: 'get',
                url: "online-bookings/" + id,
                success: function (data) {
                    $('#id').val(id);
                    $('[name="full_name"]').val(data.full_name);
                    $('[name="phone_number"]').val(data.phone_no);
                    $('[name="email"]').val(data.email);
                    $('[name="appointment_date"]').val(data.start_date);
                    $('[name="appointment_time"]').val(data.start_time);
                    $('[name="visit_reason"]').val(data.message);
                    $('input[name^="visit_history"][value="' + (data.visit_history ? '1' : '0') + '"').prop('checked', true);
                    //check if the patient has medical isurance
                    if (data.insurance_company_id != null) {
                        let company_data = {
                            id: data.insurance_company_id,
                            text: data.name
                        };
                        let newOption = new Option(company_data.text, company_data.id, true, true);
                        $('#company').append(newOption).trigger('change');
                    } else {
                        $('#company').val([]).trigger('change');
                    }

                    //check if the booking has been accepts or rejected
                    if (data.status != "Waiting") {
                        $('.action_btns').hide();
                        $('.doctor_id_field').hide();

                    } else {
                        $('.action_btns').show();
                        $('.doctor_id_field').show();
                    }

                    $.LoadingOverlay("hide");
                    $('#booking-preview-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function AcceptBooking() {
            if (confirm("{{ __('online_bookings.are_you_sure_accept') }}")) {
                $.LoadingOverlay("show");
                $('#acceptBtn').attr('disabled', true);
                $('#acceptBtn').text('{{ __('online_bookings.approving_booking') }}');
                $.ajax({
                    type: 'PUT',
                    data: $('#booking-preview-form').serialize(),
                    url: "/online-bookings/" + $('#id').val(),
                    success: function (data) {
                        $('#booking-preview-modal').modal('hide');
                        if (data.status) {
                            alert_dialog(data.message, "success");
                        } else {
                            alert_dialog(data.message, "danger");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        $('#acceptBtn').attr('disabled', false);
                        $('#acceptBtn').text('{{ __('online_bookings.accept_booking') }}');
                        json = $.parseJSON(request.responseText);
                        $.each(json.errors, function (key, value) {
                            $('.alert-danger').show();
                            $('.alert-danger').append('<p>' + value + '</p>');
                        });
                    }
                });
            }
        }

        function RejectBooking() {
            if (confirm("{{ __('online_bookings.are_you_sure_reject') }}")) {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.LoadingOverlay("show");

                $('#rejectBtn').attr('disabled', true);
                $('#rejectBtn').text('{{ __('online_bookings.processing') }}');
                $.ajax({
                    type: 'delete',
                    data: {
                        _token: CSRF_TOKEN
                    },
                    url: "online-bookings/" + $('#id').val(),
                    success: function (data) {
                        $.LoadingOverlay("hide");
                        $('#rejectBtn').attr('disabled', false);
                        $('#rejectBtn').text('{{ __('online_bookings.reject_booking') }}');
                        $('#booking-preview-modal').modal('hide');
                        if (data.status) {
                            alert_dialog(data.message, "success");
                        } else {
                            alert_dialog(data.message, "danger");
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function (request, status, error) {
                        $.LoadingOverlay("hide");
                        $('#rejectBtn').attr('disabled', false);
                        $('#rejectBtn').text('{{ __('online_bookings.reject_booking') }}');
                    }
                });
            }
        }


        // Override base template alert_dialog with page-specific behavior
        function alert_dialog(message, status) {
            swal("{{ __('online_bookings.alert') }}", message, status);
            if (status) {
                dataTable.draw(false);
            }
        }


    </script>
@endsection
