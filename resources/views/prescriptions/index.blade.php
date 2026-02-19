@extends('layouts.list-page')

@section('page_title', __('prescriptions.prescription_list'))
@section('table_id', 'prescriptions_table')

@section('table_headers')
    <th>#</th>
    <th>{{ __('patient.patient_no') }}</th>
    <th>{{ __('patient.patient_name') }}</th>
    <th>{{ __('prescriptions.drug_name') }}</th>
    <th>{{ __('prescriptions.quantity') }}</th>
    <th>{{ __('medical_treatment.directions') }}</th>
    <th>{{ __('common.created_at') }}</th>
    <th>{{ __('common.view') }}</th>
@endsection

@section('page_js')
<script type="text/javascript">
$(document).ready(function() {
    dataTable = $('#prescriptions_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('prescriptions') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'patient_no', name: 'patient_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'drug', name: 'drug'},
            {data: 'qty', name: 'qty'},
            {data: 'directions', name: 'directions', render: function(data) {
                if (data && data.length > 50) {
                    return data.substring(0, 50) + '...';
                }
                return data || '-';
            }},
            {data: 'created_at', name: 'created_at', render: function(data) {
                return data ? data.substring(0, 10) : '-';
            }},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false}
        ],
        dom: 'rtip',
        language: LanguageManager.getDataTableLang(),
        order: [[6, 'desc']]
    });
    setupEmptyStateHandler();
});
</script>
@endsection
