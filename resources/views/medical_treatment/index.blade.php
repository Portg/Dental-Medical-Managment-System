@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<link href="{{ asset('odontogram/css/estilosOdontograma.css') }}" rel="stylesheet" type="text/css"/>

<div class="note note-success">
    <div class="row">
        <div class="col-md-6">
            <p class="text-black-50"><a href="{{ url('appointments')}}" class="text-primary">{{ __('medical_treatment.view_appointments') }}
                </a> / @if(isset($patient)) {{ $patient->full_name }} ({{ $patient->patient_no
                }}) @endif
            </p>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <form action="#" id="appointment-status-form" autocomplete="off">
                    @csrf
                    <select name="appointment_status">
                        <option value="null">{{ __('medical_treatment.select_appointment_action') }}</option>
                        <option value="Treatment Complete">{{ __('medical_treatment.treatment_complete') }}</option>
                        <option value="Treatment Incomplete">{{ __('medical_treatment.treatment_incomplete') }}</option>
                    </select>
                    <input type="hidden" name="appointment_id" value="{{ $appointment_id }}">
                    <button type="button" class="btn-sm btn-primary" id="btn-appointment-status"
                            onclick="save_appointment_status();">{{ __('medical_treatment.save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<input type="hidden" value="{{ $appointment_id }}" id="global_appointment_id">
<input type="hidden" value="@if(isset($patient)) {{ $patient->id }} @endif" id="global_patient_id">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs ">
                        <li class="active" id="dental_tab_link">
                            <a href="#dental_tab" data-toggle="tab"> {{ __('medical_treatment.dental_treatment') }} </a>
                        </li>

                        <li id="chronic_diseases_tab_link">
                            <a href="#chronic_diseases_tab" data-toggle="tab"> {{ __('medical_treatment.medical_history') }} </a>
                        </li>
                        <li id="allergies_tab_link">
                            <a href="#allergies_tab" data-toggle="tab"> {{ __('medical_treatment.allergies') }} </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="dental_tab">
                            <div class="tabbable tabbable-tabdrop">
                                <ul class="nav nav-pills">

                                    <li class="active" id="dental_charting_tab_link">
                                        <a href="#dental_charting_tab" data-toggle="tab" aria-expanded="true">{{ __('medical_treatment.dental_charting') }}</a>
                                    </li>
                                    <li class="" id="dental_notes_tab_link">
                                        <a href="#dental_notes_tab" data-toggle="tab" aria-expanded="false">{{ __('medical_treatment.dental_notes') }}</a>
                                    </li>
                                    <li class="" id="prescriptions_tab_link">
                                        <a href="#prescriptions_tab" data-toggle="tab" aria-expanded="false">{{ __('medical_treatment.prescriptions') }}
                                        </a>
                                    </li>
                                    <li class=" hidden" id="dental_billing_tab_link">
                                        <a href="#dental_billing_tab" data-toggle="tab" aria-expanded="false">{{ __('medical_treatment.dental_billing') }}</a>
                                    </li>


                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="dental_charting_tab">
                                        <div class="row">
                                            <div class="portlet light">
                                                <div class="portlet-title">

                                                </div>
                                                <div class="portlet-body">
                                                    <div ng-app="app">
                                                        <odontogramageneral></odontogramageneral>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="tab-pane" id="dental_notes_tab">
                                        <div class="row">
                                            <div class="portlet light">
                                                <div class="portlet-title">

                                                    <button type="button" class="btn  blue btn-outline btn-circle btn-sm"
                                                       onclick="AddTreatment({{ $appointment_id  }})">
                                                        {{ __('medical_treatment.add_clinical_notes') }}
                                                    </button>
                                                </div>
                                                <div class="portlet-body">
                                                    <table class="table table-hover" id="dental_treatment_table">
                                                        <thead>
                                                        <tr>
                                                            <th> #</th>
                                                            <th>{{ __('medical_treatment.created_at') }}</th>
                                                            <th>{{ __('medical_treatment.clinical_notes') }}</th>
                                                            <th>{{ __('medical_treatment.treatment') }}</th>
                                                            <th>{{ __('medical_treatment.added_by') }}</th>
                                                            <th>{{ __('common.edit') }}</th>
                                                            <th>{{ __('common.delete') }}</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="prescriptions_tab">
                                        <div class="row">
                                            <div class="portlet light">
                                                <div class="portlet-title">

                                                    <div class="caption">
                                                        <span class="caption-subject font-dark bold uppercase">{{ __('medical_treatment.prescription') }}</span>
                                                        &nbsp; &nbsp; &nbsp; <a
                                                                class="btn  blue btn-outline btn-circle btn-sm"
                                                                href="#"
                                                                onclick="AddPrescription({{ $appointment_id  }})">
                                                            {{ __('medical_treatment.add_prescription') }}
                                                        </a>
                                                    </div>
                                                    <div class="actions">
                                                        <div class="btn-group btn-group-devided">

                                                            <a href="{{ url('print-prescription/'.$appointment_id) }}"
                                                               class="btn grey-salsa btn-sm"
                                                               target="_blank"> <i
                                                                        class="fa fa-print"></i>{{ __('medical_treatment.print_prescription') }}</a>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="portlet-body">
                                                    <table class="table table-striped table-bordered table-hover table-checkable order-column"
                                                           id="prescriptions_table">
                                                        <thead>
                                                        <tr>
                                                            <th> #</th>
                                                            <th>{{ __('medical_treatment.drug') }}</th>
                                                            <th>{{ __('medical_treatment.quantity') }}</th>
                                                            <th>{{ __('medical_treatment.directions') }}</th>
                                                            <th>{{ __('common.edit') }}</th>
                                                            <th>{{ __('common.delete') }}</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="dental_billing_tab">
                                        <div class="row">
                                            <div class="portlet light">
                                                <div class="portlet-title">

                                                    <button type="button" class="btn blue btn-outline btn-circle btn-sm"
                                                       onclick="AddInvoice({{ $appointment_id  }})">
                                                        {{ __('medical_treatment.create_invoice') }}
                                                    </button>
                                                </div>
                                                <div class="portlet-body">
                                                    <table class="table table-striped table-bordered table-hover table-checkable order-column"
                                                           id="dental_billing_table">
                                                        <thead>
                                                        <tr>
                                                            <th> #</th>
                                                            <th>{{ __('medical_treatment.procedure') }}</th>
                                                            <th>{{ __('medical_treatment.tooth_numbers') }}</th>
                                                            <th>{{ __('medical_treatment.amount') }}</th>
                                                            <th>{{ __('common.edit') }}</th>
                                                            <th>{{ __('common.delete') }}</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="chronic_diseases_tab">

                            <div class="row">
                                <div class="portlet light">
                                    <div class="portlet-title">

                                        <button type="button" class="btn btn-default btn-circle btn-sm"
                                           onclick="AddIllness(<?php if (isset($patient->id)) {
                                               /** @var TYPE_NAME $patient */
                                               echo $patient->id;
                                           } ?>)">
                                            {{ __('medical_treatment.add_illness') }}
                                        </button>
                                    </div>
                                    <div class="portlet-body">
                                        <table class="table table-striped table-bordered table-hover table-checkable order-column"
                                               id="chronic_diseases_table">
                                            <thead>
                                            <tr>
                                                <th> #</th>
                                                <th>{{ __('medical_treatment.illness') }}</th>
                                                <th>{{ __('medical_treatment.status') }}</th>
                                                <th>{{ __('medical_treatment.created_at') }}</th>
                                                <th>{{ __('common.edit') }}</th>
                                                <th>{{ __('common.delete') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                        </div>
                        <div class="tab-pane" id="allergies_tab">

                            <div class="row">
                                <div class="portlet light">
                                    <div class="portlet-title">

                                        <button type="button" class="btn btn-default btn-circle btn-sm"
                                           onclick="AddAllergy(<?php if (isset($patient->id)) {
                                               /** @var TYPE_NAME $patient */
                                               echo $patient->id;
                                           } ?>)">
                                            {{ __('medical_treatment.add_allergies') }}
                                        </button>
                                    </div>
                                    <div class="portlet-body">
                                        <table class="table table-striped table-bordered table-hover table-checkable order-column"
                                               id="allergies_table">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ __('medical_treatment.allergies') }}</th>
                                                <th>{{ __('medical_treatment.created_at') }}</th>
                                                <th>{{ __('common.edit') }}</th>
                                                <th>{{ __('common.delete') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('medical_history.chronic_diseases.create')
@include('medical_history.allergies.create')

@include('medical_treatment.prescriptions.create')
@include('medical_treatment.prescriptions.edit')
{{--//dental treatment--}}
@include('medical_treatment.treatment.create')

{{--//dental invoicing--}}
@include('appointments.invoices.create')
@include('invoices.show.edit_invoice')

@endsection
@section('js')
    <script>
        let global_patient_id = $('#global_patient_id').val();
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/chronic_diseases.js') }}"></script>
    <script src="{{ asset('include_js/allergies.js') }}"></script>
    <script src="{{ asset('include_js/prescriptions.js') }}"></script>
    {{--    //dental treatment--}}
    <script src="{{ asset('include_js/treatment.js') }}"></script>

    {{--    //dental invoicing--}}
    <script src="{{ asset('include_js/invoicing.js') }}"></script>
    {{--dental charting plugins--}}
    <script src="{{ asset('odontogram/scripts/angular.js') }}"></script>
    <!-- Angular Modulos-->
    <script type="text/javascript" src="{{ asset('odontogram/scripts/modulos/app.js') }}"></script>
    <!-- Angular Controsideres-->
    <script type="text/javascript" src="{{ asset('odontogram/scripts/controladores/controller.js') }}"></script>

    <script type="text/javascript" src="{{ asset('odontogram/scripts/jquery-odontograma.js') }}"></script>
    <!--Angular Directives-->
    <script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/canvasodontograma.js') }}"></script>
    <script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/opcionescanvas.js') }}"></script>
    <script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/odontogramaGeneral.js') }}"></script>

    <script type="text/javascript">
        //save appointment status
        function save_appointment_status() {
            swal({
                    title: "{{ __('medical_treatment.are_you_sure_save') }}",
                    // text: "This record was deleted before by the user",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn green-meadow",
                    confirmButtonText: "{{ __('medical_treatment.yes_save') }}",
                    closeOnConfirm: false
                },
                function () {
                    $.LoadingOverlay("show");
                    $('#btn-appointment-status').attr('disabled', true);
                    $('#btn-appointment-status').text('{{ __('common.processing') }}');
                    $.ajax({
                        type: 'POST',
                        data: $('#appointment-status-form').serialize(),
                        url: "/appointment-status",
                        success: function (data) {
                            $.LoadingOverlay("hide");
                            swal("{{ __('common.alert') }}", data.message, "success");
                            setTimeout(function () {
                                location.replace('/doctor-appointments');
                            }, 1900);
                        },
                        error: function (error) {
                            $.LoadingOverlay("hide");
                            $('#btn-appointment-status').attr('disabled', false);
                            $('#btn-appointment-status').text('{{ __('common.save') }}');
                        }
                    });
                });
        }


    </script>
@endsection





