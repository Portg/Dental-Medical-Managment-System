@extends('layouts.list-page')

@section('page_title', __('insurance_reports.title'))
@section('table_id', 'insurance-report-table')

@section('filter_primary')
    <div class="col-md-3">
        <div class="filter-label">{{ __('insurance_reports.insurance_company') }}</div>
        <select id="company" name="insurance_company_id" class="form-control select2" style="width: 100%;"></select>
    </div>
    <div class="col-md-2">
        <div class="input-icon">
            <div class="filter-label">{{ __('insurance_reports.claims_start_date') }}</div>
            <input type="date" name="start_date" id="start_date"
                   class="form-control datepicker-autoclose" placeholder="{{ __('datetime.placeholder_start_date') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="input-icon">
            <div class="filter-label">{{ __('insurance_reports.claims_end_date') }}</div>
            <input type="date" name="end_date" id="end_date"
                   class="form-control datepicker-autoclose" placeholder="{{ __('datetime.placeholder_end_date') }}">
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('insurance_reports.date') }}</th>
    <th>{{ __('insurance_reports.insurance_company') }}</th>
    <th>{{ __('insurance_reports.invoice_no') }}</th>
    <th>{{ __('insurance_reports.customer') }}</th>
    <th>{{ __('insurance_reports.procedure') }}</th>
    <th>{{ __('insurance_reports.fees') }}</th>
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'insurance_reports': @json(__('insurance_reports')),
            'common': @json(__('common'))
        });

        dataTable = $('#insurance-report-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/insurance-reports') }}",
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.company = $('#company').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', 'visible': false},
                {data: 'created_at', name: 'created_at'},
                {data: 'insurance_company', name: 'insurance_company'},
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'patient', name: 'patient'},
                {data: 'services_provided', name: 'services_provided'},
                {data: 'amount', name: 'amount'}
            ]
        });

        setupEmptyStateHandler();

        // Insurance company select2 search
        $('#company').select2({
            language: '{{ app()->getLocale() }}',
            placeholder: "{{ __('insurance_reports.choose_insurance_company') }}",
            minimumInputLength: 2,
            allowClear: true,
            ajax: {
                url: "{{ url('/search-insurance-company') }}",
                dataType: 'json',
                data: function (params) {
                    return {
                        q: $.trim(params.term)
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });
    });
</script>
@endsection
