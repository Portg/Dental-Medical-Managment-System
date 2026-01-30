@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-note font-green"></i>
                    <span class="caption-subject font-green bold uppercase">{{ __('prescriptions.prescription_list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="prescriptions_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('patient.patient_no') }}</th>
                            <th>{{ __('patient.patient_name') }}</th>
                            <th>{{ __('prescriptions.drug_name') }}</th>
                            <th>{{ __('prescriptions.quantity') }}</th>
                            <th>{{ __('medical_treatment.directions') }}</th>
                            <th>{{ __('common.created_at') }}</th>
                            <th>{{ __('common.view') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
    $('#prescriptions_table').DataTable({
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
        language: LanguageManager.getDataTableLanguage(),
        order: [[6, 'desc']]
    });
});
</script>
@endsection
