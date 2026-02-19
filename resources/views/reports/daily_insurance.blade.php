@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.todays_insurance_report'))
@section('table_id', 'sample_1')

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.time') }}</th>
    <th>{{ __('report.surname') }}</th>
    <th>{{ __('report.othername') }}</th>
    <th>{{ __('common.amount') }}</th>
    <th>{{ __('report.added_by') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script type="text/javascript">
    $(function () {
        dataTable = $('#sample_1').DataTable({
            language: LanguageManager.getDataTableLang(),
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ url('/todays-insurance/') }}",
                data: function (d) {
                    d.search = $('input[type="search"]').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                {data: 'created_date', name: 'created_date'},
                {data: 'surname', name: 'surname'},
                {data: 'othername', name: 'othername'},
                {data: 'amount', name: 'amount'},
                {data: 'added_by', name: 'added_by'},
            ]
        });

        setupEmptyStateHandler();
    });
</script>
@endsection
