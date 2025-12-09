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
                    <span class="caption-subject"> {{__('patient.patient_management')}}/ {{__('patient.patients')}}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <a class="btn blue btn-outline sbold" href="#"
                                   onclick="createRecord()"> {{__('patient.add_new_patient')}} <i
                                            class="fa fa-plus"></i> </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group pull-right">
                                <a href="{{ url('export-patients') }}" class="text-danger">
                                    <i class="icon-cloud-download"></i> {{__('common.download_excel_report')}} </a>
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
                                    <div class="form-group">
                                        <label class="control-label col-md-3">{{__('patient.insurance_company')}}</label>
                                        <div class="col-md-9">
                                            <select id="filter_company" name="filter_company" class="form-control"
                                                    { style="width: 100%;"></select>
                                        </div>
                                    </div>
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
                                            <button type="button" id="customFilterBtn" class="btn purple-intense">{{__('patient.filter_patients')}}
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
                       id="patients-table">
                    <thead>
                    <tr>
                        <th>{{ __('patient.id') }}</th>
                        <th>{{ __('patient.surname') }}</th>
                        <th>{{ __('patient.other_name') }}</th>
                        <th>{{ __('patient.gender') }}</th>
                        <th>{{ __('patient.dob') }}</th>
                        <th>{{ __('patient.email_address') }}</th>
                        <th>{{ __('patient.phone_no') }}</th>
                        <th>{{ __('patient.next_of_kin') }}</th>
                        <th>{{ __('patient.medical_aid') }}</th>
                        <th>{{ __('patient.added_by') }}</th>
                        <th>{{ __('patient.action') }}</th>
                    </tr>
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
@include('patients.create')
@include('patients.patient_history')
@endsection
@section('js')

    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/lang-zh-CN.js') }}"></script>

<script type="text/javascript">
    // Internationalization variables
    const i18n = {
        saveRecord: "{{ __('common.save_record') }}",
        processing: "{{ __('common.processing') }}",
        updateRecord: "{{ __('common.update_record') }}",
        updating: "{{ __('common.updating') }}",
        confirmDeleteTitle: "{{ __('common.confirm_delete_title') }}",
        confirmDeleteMessage: "{{ __('patient.confirm_delete_message') }}",
        confirmDeleteButton: "{{ __('common.confirm_delete_button') }}",
        alert: "{{ __('common.alert') }}",
        chooseInsuranceCompany: "{{ __('patient.choose_insurance_company') }}"
    };
</script>

<script type="text/javascript">

        let input = document.querySelector("#telephone");
        window.intlTelInput(input, {
            preferredCountries: ["ug", "us"],
            autoPlaceholder: "off",
            utilsScript: "{{ asset('backend/assets/global/scripts/utils.js') }}",
        });
        var iti = window.intlTelInputGlobals.getInstance(input);


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
            default_todays_data();  //filter patient date

            var table = $('#patients-table').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('/patients/') }}",
                    data: function (d) {
                        d.start_date = $('.start_date').val();
                        d.end_date = $('.end_date').val();
                        d.insurance_company = $('#filter_company').val();
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
                    {data: 'surname', name: 'surname'},
                    {data: 'othername', name: 'othername'},
                    {data: 'gender', name: 'gender', 'visible': false},
                    {data: 'dob', name: 'dob', 'visible': false},
                    {data: 'email', name: 'email'},
                    {data: 'phone_no', name: 'phone_no'},
                    {data: 'next_of_kin', name: 'next_of_kin', 'visible': false},
                    {data: 'medical_insurance', name: 'medical_insurance'},
                    {data: 'addedBy', name: 'addedBy'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });


        });
        $('#customFilterBtn').click(function () {
            $('#patients-table').DataTable().draw(true);
        });

        function createRecord() {
            $("#patient-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text(i18n.saveRecord);
            $('#patients-modal').modal('show');
        }


        //filter insurance companies
        $('#filter_company').select2({
            // placeholder: "Insurance Company",
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
        //filter insurance companies
        $('#company').select2({
            placeholder: i18n.chooseInsuranceCompany,
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

        // first check if the patient belongs to insurance or hide the insurance select
        var radios = document.getElementsByName('has_insurance');

        for (var i = 0, length = radios.length; i < length; i++) {
            if (radios[i].checked) {
                // do whatever you want with the checked radio
                alert(radios[i].value);

                // only one radio can be logically checked, don't check the rest
                break;
            }
        }


        $(document).ready(function () {

            //hide the insurance companies
            $('#company').val([]).trigger('change');
            $("#company").select2("val", "");
            $('.insurance_company').hide();
            $("input[type=radio][name=has_insurance]").on("change", function () {
                let action = $("input[type=radio][name=has_insurance]:checked").val();

                if (action == "No") {
                    //change the value back to default
                    $('#company').val([]).trigger('change');
                    //now hide the view
                    $('.insurance_company').hide();
                    $('#company').next(".select2-container").hide();
                } else {
                    //show the select
                    $('.insurance_company').show();
                    $('#company').next(".select2-container").show();
                }

            });
        });

        function save_data() {
            //check save method
            var id = $('#id').val();
            //update the country code phone number
            let number = iti.getNumber();
            $('#phone_number').val(number);

            if (id == "") {
                save_new_record();
            } else {
                update_record();
            }
        }

        function save_new_record() {
            $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text(i18n.processing);
            $.ajax({
                type: 'POST',
                data: $('#patient-form').serialize(),
                url: "/patients",
                success: function (data) {
                    $('#patients-modal').modal('hide');
                    $.LoadingOverlay("hide");
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text(i18n.saveRecord);
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text(i18n.saveRecord);
                    json = $.parseJSON(request.responseText);
                    $.each(json.errors, function (key, value) {
                        $('.alert-danger').show();
                        $('.alert-danger').append('<p>' + value + '</p>');
                    });
                }
            });
        }

        function editRecord(id) {
            $("#patient-form")[0].reset();
            $('#id').val(''); ///always reset hidden form fields
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text(i18n.updateRecord);
            $.LoadingOverlay("show");
            $.ajax({
                type: 'get',
                url: "patients/" + id + "/edit",
                success: function (data) {
                    console.log(data);
                    $('#id').val(id);
                    $('[name="surname"]').val(data.patient.surname);
                    $('[name="othername"]').val(data.patient.othername);
                    $('input[name^="gender"][value="' + data.patient.gender + '"').prop('checked', true);
                    $('[name="dob"]').val(data.patient.dob);
                    $('[name="email"]').val(data.patient.email);
                    $('[name="telephone"]').val(data.patient.phone_no);
                    if (data.patient.phone_no != null) {
                        iti.setNumber(data.patient.phone_no);// change the flag
                    }
                    $('[name="alternative_no"]').val(data.patient.alternative_no);
                    $('[name="nin"]').val(data.patient.nin);
                    $('[name="profession"]').val(data.patient.profession);
                    $('[name="next_of_kin"]').val(data.patient.next_of_kin);
                    $('[name="next_of_kin_no"]').val(data.patient.next_of_kin_no);
                    $('[name="next_of_kin_address"]').val(data.patient.next_of_kin_address);

                    $('[name="address"]').val(data.patient.address);
                    $('input[name^="has_insurance"][value="' + data.patient.has_insurance + '"').prop('checked', true);

                    if (data.patient.has_insurance == "No") {
                        //change the value back to default
                        $('#company').val([]).trigger('change');
                        //now hide the view
                        $('.insurance_company').hide();
                        $('#company').next(".select2-container").hide();
                    } else {
                        //show the select and fetch the data for the selected company
                        let company_data = {
                            id: data.patient.insurance_company_id,
                            text: data.company
                        };
                        let newOption = new Option(company_data.text, company_data.id, true, true);
                        $('#company').append(newOption).trigger('change');
                        $('.insurance_company').show();
                        $('#company').next(".select2-container").show();
                    }

                    $.LoadingOverlay("hide");
                    $('#patients-modal').modal('show');

                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        }

        function update_record() {
            $.LoadingOverlay("show");
            $('#btnSave').attr('disabled', true);
            $('#btnSave').text(i18n.updating);
            $.ajax({
                type: 'PUT',
                data: $('#patient-form').serialize(),
                url: "/patients/" + $('#id').val(),
                success: function (data) {
                    $('#patients-modal').modal('hide');
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#btnSave').attr('disabled', false);
                    $('#btnSave').text(i18n.updateRecord);

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
                    title: i18n.confirmDeleteTitle,
                    text: i18n.confirmDeleteMessage,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: i18n.confirmDeleteButton,
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
                        url: "patients/" + id,
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


        //patient history
        function patientHistory(patient_id) {
            $('#patient-history-modal').modal('show')
        }


        function alert_dialog(message, status) {
            swal(i18n.alert, message, status);
            if (status) {
                let oTable = $('#patients-table').dataTable();
                oTable.fnDraw(false);
            }
        }

        function getPatientMedicalHistory(patient_id) {
            $.LoadingOverlay("show");
            $('.noResultsText').hide();
            $.ajax({
                type: 'get',
                url: "patients/" + patient_id + "/medicalHistory",
                success: function (data) {
                    $('.patientInfoText').text(data.patientInfor.surname + " " + data.patientInfor.othername)
                    if (data.treatmentHistory.length != 0) {
                        convertJsontoHtmlTable(data.treatmentHistory);
                    } else {
                        $('.noResultsText').show();
                    }
                    $.LoadingOverlay("hide");
                    $('#patient-history-modal').modal('show')
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                    $('#patient-history-modal').modal('hide')
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
            var treatmentHistoryTable = document.createElement("table");
            treatmentHistoryTable.classList.add("table");
            treatmentHistoryTable.classList.add("table-striped");
            treatmentHistoryTable.classList.add("table-bordered");
            treatmentHistoryTable.classList.add("table-hover")

            //Creating header of the HTML table using
            //tr
            var tr = treatmentHistoryTable.insertRow(-1);

            for (var i = 0; i < tablecolumns.length; i++) {
                //header
                var th = document.createElement("th");
                th.innerHTML = tablecolumns[i];
                tr.appendChild(th);
            }

            // Add jsonResponseData in table as tr or rows
            for (var i = 0; i < jsonResponseData.length; i++) {
                tr = treatmentHistoryTable.insertRow(-1);
                for (var j = 0; j < tablecolumns.length; j++) {
                    var tabCell = tr.insertCell(-1);
                    tabCell.innerHTML = jsonResponseData[i][tablecolumns[j]];
                }
            }

            //Final step , append html table to the container div
            var patientHistoryContainer = document.getElementById("patientHistoryContainer");
            patientHistoryContainer.innerHTML = "";
            patientHistoryContainer.appendChild(treatmentHistoryTable);
        }

    </script>
@endsection





