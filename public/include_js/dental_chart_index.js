$(function () {
    var cfg = window.DentalChartIndexConfig || {};

    if (cfg.flashError) {
        toastr.error(cfg.flashError);
    }

    $('#chart_patient_select').select2({
        width: '240px',
        language: cfg.locale,
        placeholder: LanguageManager.trans('odontogram.search_patient'),
        allowClear: true,
        ajax: {
            url: cfg.searchPatientUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });

    $('#btn_open_chart').on('click', function () {
        var patientId = $('#chart_patient_select').val();
        if (!patientId) {
            toastr.warning(LanguageManager.trans('odontogram.select_patient'));
            return;
        }
        window.location.href = cfg.openForPatientUrl + '/' + patientId;
    });

    dataTable = $('#dental_chart_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: cfg.listUrl,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'patient_no', name: 'patient_no' },
            { data: 'patient_name', name: 'patient_name' },
            { data: 'tooth_count', name: 'tooth_count', orderable: false, searchable: false },
            { data: 'last_updated', name: 'last_updated' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        dom: 'rtip',
        language: LanguageManager.getDataTableLang(),
        order: [[4, 'desc']]
    });
    setupEmptyStateHandler();
});
