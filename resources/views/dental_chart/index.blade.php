@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-grid font-green"></i>
                    <span class="caption-subject font-green bold uppercase">{{ __('odontogram.dental_chart_list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="note note-info">
                    <p><i class="fa fa-info-circle"></i> {{ __('odontogram.go_to_appointment') }}</p>
                </div>
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="dental_chart_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('odontogram.patient_no') }}</th>
                            <th>{{ __('odontogram.patient_name') }}</th>
                            <th>{{ __('odontogram.tooth_count') }}</th>
                            <th>{{ __('odontogram.last_updated') }}</th>
                            <th>{{ __('common.actions') }}</th>
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
    $('#dental_chart_table').DataTable({
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
        language: LanguageManager.getDataTableLanguage(),
        order: [[4, 'desc']]
    });
});
</script>
@endsection
