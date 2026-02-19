@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.todays_expense_report'))
@section('table_id', 'sample_1')

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.time') }}</th>
    <th>{{ __('common.name') }}</th>
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
                url: "{{ url('/todays-expenses/') }}",
                data: function (d) {
                    d.search = $('input[type="search"]').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': true},
                {data: 'created_date', name: 'created_date'},
                {data: 'name', name: 'name'},
                {data: 'amount', name: 'amount'},
                {data: 'added_by', name: 'added_by'},
            ]
        });

        setupEmptyStateHandler();
    });
</script>
@endsection
