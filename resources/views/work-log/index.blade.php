@extends('layouts.list-page')

@section('page_title', __('work_log.list_title'))
@section('table_id', 'sample_1')

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('work_log.col_date') }}</th>
    <th>{{ __('work_log.col_name') }}</th>
    <th>{{ __('work_log.col_gender') }}</th>
    <th>{{ __('work_log.col_age') }}</th>
    <th>{{ __('work_log.col_visit_type') }}</th>
    <th>{{ __('work_log.col_phone') }}</th>
    <th>{{ __('work_log.col_tooth') }}</th>
    <th>{{ __('work_log.col_diagnosis') }}</th>
    <th>{{ __('work_log.col_doctor') }}</th>
    <th>{{ __('work_log.col_amount') }}</th>
    <th>{{ __('work_log.col_invoice') }}</th>
@endsection

@section('page_js')
    <script type="text/javascript">
        $(function () {
            LanguageManager.loadAllFromPHP({
                'work_log': @json(__('work_log'))
            });

            dataTable = $('#sample_1').DataTable({
                destroy: true,
                processing: true,
                serverSide: true,
                language: LanguageManager.getDataTableLang(),
                ajax: {
                    url: "{{ url('/work-logs') }}",
                    data: function (d) {}
                },
                dom: 'rtip',
                order: [[1, 'desc']],
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'log_date', name: 'log_date'},
                    {data: 'patient_link', name: 'patient_name'},
                    {data: 'gender', name: 'gender'},
                    {data: 'age', name: 'age'},
                    {data: 'visit_type_label', name: 'visit_type'},
                    {data: 'phone', name: 'phone'},
                    {data: 'tooth_position', name: 'tooth_position'},
                    {data: 'diagnosis', name: 'diagnosis'},
                    {data: 'doctor_label', name: 'doctor_name_raw'},
                    {data: 'amount', name: 'amount'},
                    {data: 'invoice_label', name: 'invoice_id', orderable: false}
                ]
            });

            if (typeof setupEmptyStateHandler === 'function') {
                setupEmptyStateHandler();
            }
        });
    </script>
@endsection
