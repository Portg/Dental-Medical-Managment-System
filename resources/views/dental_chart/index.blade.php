@extends('layouts.list-page')

@section('page_title', __('odontogram.dental_chart_list'))
@section('table_id', 'dental_chart_table')

@section('header_actions')
    <div class="dental-chart-open-bar">
        <select id="chart_patient_select" class="form-control" style="width: 240px;"></select>
        <button type="button" class="btn btn-primary" id="btn_open_chart">
            <i class="fa fa-edit"></i> {{ __('odontogram.open_chart') }}
        </button>
    </div>
@endsection

@section('table_headers')
    <th>#</th>
    <th>{{ __('odontogram.patient_no') }}</th>
    <th>{{ __('odontogram.patient_name') }}</th>
    <th>{{ __('odontogram.tooth_count') }}</th>
    <th>{{ __('odontogram.last_updated') }}</th>
    <th>{{ __('common.actions') }}</th>
@endsection

@section('page_css')
<link href="{{ asset('css/dental_chart_index.css') }}?v={{ filemtime(public_path('css/dental_chart_index.css')) }}" rel="stylesheet">
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'odontogram': @json(__('odontogram'))
    });
    window.DentalChartIndexConfig = {
        searchPatientUrl: "{{ url('search-patient') }}",
        openForPatientUrl: "{{ url('dental-charting/for-patient') }}",
        listUrl: "{{ url('dental-charting') }}",
        locale: "{{ app()->getLocale() }}",
        flashError: @json(session('error'))
    };
</script>
<script src="{{ asset('include_js/dental_chart_index.js') }}?v={{ filemtime(public_path('include_js/dental_chart_index.js')) }}"></script>
@endsection
