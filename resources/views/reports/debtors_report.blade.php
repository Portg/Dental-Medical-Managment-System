@extends('layouts.list-page')

{{-- ========================================================================
     Page Configuration
     ======================================================================== --}}
@section('page_title', __('report.receivables_report'))
@section('table_id', 'payment-report')

{{-- ========================================================================
     Header Actions
     ======================================================================== --}}
@section('header_actions')
    <a href="{{ url('debtors-export') }}" class="text-danger">
        <i class="icon-cloud-download"></i> {{ __('report.download_excel_report') }}
    </a>
@endsection

{{-- ========================================================================
     Table Headers
     ======================================================================== --}}
@section('table_headers')
    <th>{{ __('report.invoice_no') }}</th>
    <th>{{ __('report.invoice_date') }}</th>
    <th>{{ __('report.first_name') }}</th>
    <th>{{ __('report.last_name') }}</th>
    <th>{{ __('report.phone_no') }}</th>
    <th>{{ __('report.invoice_amount') }}</th>
    <th>{{ __('report.paid_amount') }}</th>
    <th>{{ __('report.outstanding_balance') }}</th>
@endsection

{{-- ========================================================================
     Page-specific JavaScript
     ======================================================================== --}}
@section('page_js')
<script type="text/javascript">
    $(function () {
        dataTable = $('#payment-report').DataTable({
            language: LanguageManager.getDataTableLang(),
            processing: true,
            serverSide: true,
            ajax: {
                url: "/debtors",
                data: function (d) {
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'invoice_date', name: 'invoice_date'},
                {data: 'surname', name: 'surname'},
                {data: 'othername', name: 'othername'},
                {data: 'phone_no', name: 'phone_no'},
                {data: 'invoice_amount', name: 'invoice_amount'},
                {data: 'amount_paid', name: 'amount_paid'},
                {data: 'outstanding_balance', name: 'outstanding_balance'}
            ]
        });

        setupEmptyStateHandler();
    });
</script>
@endsection
