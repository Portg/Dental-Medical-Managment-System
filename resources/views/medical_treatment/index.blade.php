@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('page_title', __('medical_treatment.page_title'))
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link href="{{ asset('css/dental-chart-editor.css') }}?v={{ filemtime(public_path('css/dental-chart-editor.css')) }}" rel="stylesheet" type="text/css"/>
    {{-- 划价面板样式，与患者页同一份 --}}
    <link href="{{ asset('css/patient-billing.css') }}?v={{ filemtime(public_path('css/patient-billing.css')) }}" rel="stylesheet" type="text/css"/>
@endsection

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
<input type="hidden" value="{{ $patient->id ?? '' }}" id="global_patient_id">
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
                                    {{-- 划价 Tab 自首次提交起就带 hidden，属遗留状态而非有意关闭：
                                         其 DataTable（/appointment-invoice-items/{id}）与开单表单都已实现，
                                         且提交到 /invoices，与预约页开单走同一条 InvoiceService 主流程，
                                         折扣审批与库存扣减规则一致，不存在旁路。
                                         这里按 view-invoices 权限开放，避免对无账单权限的角色暴露后必然 403。 --}}
                                    @can('view-invoices')
                                        <li class="" id="dental_billing_tab_link">
                                            <a href="#dental_billing_tab" data-toggle="tab" aria-expanded="false">{{ __('medical_treatment.dental_billing') }}</a>
                                        </li>
                                    @endcan


                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="dental_charting_tab">
                                        <div class="portlet light">
                                            <div class="portlet-body">
                                                @include('dental_chart.partials.fdi_editor')
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

                                                    {{-- 开加工单 —— 医生是在诊疗页决定要做修复体的，入口就该在这儿。
                                                         加工单表上一直有 appointment_id / medical_case_id 两列，
                                                         但此前没有任何入口会带上，加工单挂不到就诊上。
                                                         跳到加工单页并带上下文，复用那边的弹窗（见 openLabCaseWithContext）。 --}}
                                                    @can('manage-labs')
                                                        @if(!empty($patient))
                                                            <a class="btn green btn-outline btn-circle btn-sm"
                                                               href="{{ url('lab-cases') }}?{{ http_build_query([
                                                                    'patient_id'     => $patient->id,
                                                                    'patient_text'   => $patient->patient_no . ' - ' . $patient->full_name,
                                                                    'appointment_id' => $appointment_id,
                                                               ]) }}">
                                                                <i class="fa fa-cogs"></i> {{ __('medical_treatment.create_lab_case') }}
                                                            </a>
                                                        @endif
                                                    @endcan
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
                                        {{-- 划价面板与患者页共用同一个 partial + 同一个 BillingModule。
                                             此前这里是另一条路（AddInvoice 弹窗 → POST /invoices），
                                             与患者页的 /billing/create 各自实现折扣、牙位、医生归属，
                                             改一处漏一处。现在只剩一套。

                                             面板提交时会带上 appointment_id，账单挂到这次就诊上，
                                             下面「本次已划价」表才看得到（该表按 invoices.appointment_id 过滤）。 --}}
                                        @can('create-invoices')
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="portlet light bordered">
                                                        <div class="portlet-body">
                                                            @include('billing.partials.charge_panel')
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endcan

                                        <div class="row">
                                            <div class="portlet light">
                                                <div class="portlet-title">
                                                    <div class="caption">
                                                        <span class="caption-subject font-dark bold uppercase">{{ __('medical_treatment.billed_this_visit') }}</span>
                                                    </div>
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
{{-- 原来这里还 include 了 appointments.invoices.create（开单弹窗）。
     诊疗页已改用共享划价面板，那个弹窗在本页没有任何触发点了，去掉。
     预约页与今日工作页各自 include 并各有自己的内联实现，不受影响。 --}}
@include('invoices.show.edit_invoice')

{{-- 划价面板的右侧详情抽屉由 BillingModule 使用 --}}
<div class="billing-panel-overlay" id="billingPanelOverlay"></div>
<div class="billing-side-panel" id="billingSidePanel" role="dialog" aria-modal="true">
    <div class="billing-panel-header">
        <h4 id="billingPanelTitle">{{ __('invoices.panel_invoice_detail') }}</h4>
        <button class="billing-panel-close" id="billingPanelClose" aria-label="Close">&#x2715;</button>
    </div>
    <div class="billing-panel-body" id="billingPanelBody"></div>
</div>

@endsection
@section('js')
    <script>
        LanguageManager.loadFromPHP(@json(__('odontogram')), 'odontogram');
        LanguageManager.loadFromPHP(@json(__('medical_treatment')), 'medical_treatment');
        {{-- 划价面板（BillingModule）用的是 invoices.* 与 messages.* 两组键 --}}
        LanguageManager.loadFromPHP(@json(__('invoices')), 'invoices');
        LanguageManager.loadFromPHP(@json(__('messages')), 'messages');
        let global_patient_id = ($('#global_patient_id').val() || '').trim();
    </script>
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/chronic_diseases.js') }}"></script>
    <script src="{{ asset('include_js/allergies.js') }}"></script>
    <script src="{{ asset('include_js/prescriptions.js') }}?v={{ filemtime(public_path('include_js/prescriptions.js')) }}"></script>
    {{--    //dental treatment--}}
    <script src="{{ asset('include_js/treatment.js') }}?v={{ filemtime(public_path('include_js/treatment.js')) }}"></script>

    {{--    //dental invoicing--}}
    <script src="{{ asset('include_js/invoicing.js') }}?v={{ filemtime(public_path('include_js/invoicing.js')) }}"></script>
    @can('create-invoices')
        {{-- 与患者页同一个划价模块 --}}
        <script src="{{ asset('include_js/patient_billing.js') }}?v={{ filemtime(public_path('include_js/patient_billing.js')) }}"></script>
    @endcan
    <script src="{{ asset('include_js/dental_chart_editor.js') }}?v={{ filemtime(public_path('include_js/dental_chart_editor.js')) }}"></script>

    @can('create-invoices')
        @if(!empty($patient))
            <script>
                {{-- 划价面板在划价 Tab 首次展开时初始化：面板一进来就要拉服务目录，
                     不展开就初始化等于每次打开诊疗页都白拉一次。
                     invoicing.js 的 #dental_billing_tab_link click 负责刷明细表，
                     这里用 shown.bs.tab，两者不冲突。 --}}
                $('#dental_billing_tab_link a').on('shown.bs.tab', function () {
                    if (typeof BillingModule === 'undefined') return;
                    BillingModule.init({{ $patient->id }}, {!! json_encode($doctors ?? []) !!}, {
                        appointmentId: {{ (int) $appointment_id }},
                        {{-- 划价成功后刷新「本次已划价」表，否则刚开的单要手动刷页面才看得到 --}}
                        onSaved: function () {
                            if (typeof load_dental_billing === 'function') {
                                load_dental_billing();
                            }
                        }
                    });
                });
            </script>
        @endif
    @endcan

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





