@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<link href="{{ asset('odontogram/css/estilosOdontograma.css') }}" rel="stylesheet" type="text/css"/>

<div class="note note-success">
    <div class="row">
        <div class="col-md-6">
            <p class="text-black-50">
                <a href="{{ url('dental-charting')}}" class="text-primary">{{ __('odontogram.dental_charting') }}</a>
                / @if(isset($patient)) {{ $patient->full_name }} ({{ $patient->patient_no }}) @endif
            </p>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ url('dental-charting') }}" class="btn btn-sm btn-default">
                    <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
                </a>
            </div>
        </div>
    </div>
</div>

<input type="hidden" value="{{ $appointment_id }}" id="global_appointment_id">
<input type="hidden" value="@if(isset($patient)) {{ $patient->id }} @endif" id="global_patient_id">

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-grid font-green"></i>
                    <span class="caption-subject font-green bold uppercase">{{ __('odontogram.dental_charting') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div ng-app="app">
                    <odontogramageneral></odontogramageneral>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>

@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
{{-- Dental charting plugins --}}
<script src="{{ asset('odontogram/scripts/angular.js') }}"></script>
<!-- Angular Modulos-->
<script type="text/javascript" src="{{ asset('odontogram/scripts/modulos/app.js') }}"></script>
<!-- Angular Controllers-->
<script type="text/javascript" src="{{ asset('odontogram/scripts/controladores/controller.js') }}"></script>
<script type="text/javascript" src="{{ asset('odontogram/scripts/jquery-odontograma.js') }}"></script>
<!--Angular Directives-->
<script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/canvasodontograma.js') }}"></script>
<script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/opcionescanvas.js') }}"></script>
<script type="text/javascript" src="{{ asset('odontogram/scripts/directivas/odontogramaGeneral.js') }}"></script>
@endsection
