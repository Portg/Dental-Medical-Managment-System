@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('page_title', __('odontogram.dental_charting'))

@section('css')
    @include('layouts.page_loader')
    <link href="{{ asset('css/dental-chart-editor.css') }}?v={{ filemtime(public_path('css/dental-chart-editor.css')) }}" rel="stylesheet" type="text/css"/>
@endsection

@section('content')
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
<input type="hidden" value="{{ $patient->id ?? '' }}" id="global_patient_id">

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
                @include('dental_chart.partials.fdi_editor')
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    LanguageManager.loadFromPHP(@json(__('odontogram')), 'odontogram');
</script>
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/dental_chart_editor.js') }}?v={{ filemtime(public_path('include_js/dental_chart_editor.js')) }}"></script>
@endsection
