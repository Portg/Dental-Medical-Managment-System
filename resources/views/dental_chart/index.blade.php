@extends('layouts.list-page')

@section('page_title', __('odontogram.dental_chart_list'))
@section('table_id', 'dental_chart_table')

@section('table_headers')
    <th>#</th>
    <th>{{ __('odontogram.patient_no') }}</th>
    <th>{{ __('odontogram.patient_name') }}</th>
    <th>{{ __('odontogram.tooth_count') }}</th>
    <th>{{ __('odontogram.last_updated') }}</th>
    <th>{{ __('common.actions') }}</th>
@endsection

@section('filter_area')
    <div class="note note-info">
        <p><i class="fa fa-info-circle"></i> {{ __('odontogram.go_to_appointment') }}</p>
    </div>
@endsection

@section('page_js')
<script type="text/javascript">
$(document).ready(function() {
    dataTable = $('#dental_chart_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('dental-charting') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'patient_no', name: 'patient_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'tooth_count', name: 'tooth_count', orderable: false, searchable: false},
            {data: 'last_updated', name: 'last_updated'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: 'rtip',
        language: LanguageManager.getDataTableLang(),
        order: [[4, 'desc']]
    });
    setupEmptyStateHandler();
});
</script>
@endsection
