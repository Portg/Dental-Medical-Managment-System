$(document).ready(function() {
    $('#filter_doctor').select2({
        language: window.MedicalCasesIndexConfig.locale,
        placeholder: LanguageManager.trans('medical_cases.select_doctor'),
        allowClear: true,
        ajax: {
            url: '/search-doctor', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term || '' }; },
            processResults: function(d) { return { results: d }; },
            cache: true
        }
    });
    $('#filter_patient').select2({
        language: window.MedicalCasesIndexConfig.locale,
        placeholder: LanguageManager.trans('medical_cases.select_patient'),
        allowClear: true, minimumInputLength: 2,
        ajax: {
            url: '/search-patient', dataType: 'json', delay: 250,
            data: function(p) { return { q: p.term }; },
            processResults: function(d) { return { results: d }; },
            cache: true
        }
    });

    var dtm = new DataTableManager({
        tableId: '#medical_cases_table',
        ajaxUrl: '/medical-cases',
        order: [[5, 'desc']],
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'case_no', name: 'case_no'},
            {data: 'title', name: 'title'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'doctor_name', name: 'doctor_name'},
            {data: 'case_date', name: 'case_date'},
            {data: 'statusBadge', name: 'statusBadge', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        filterParams: function(d) {
            d.search_term = $('#quickSearch').val();
            d.status = $('#filter_status').val();
            d.doctor_id = $('#filter_doctor').val();
            d.patient_id = $('#filter_patient').val();
            d.start_date = $('#filter_start_date').val();
            d.end_date = $('#filter_end_date').val();
        },
        navigateCreate: true,
        createUrl: window.MedicalCasesIndexConfig.createUrl,
        navigateEdit: true,
        editUrl: '/medical-cases/{id}/edit'
    });

    dtm.initQuickSearch('#quickSearch');
    $('#filter_status, #filter_doctor, #filter_patient').on('change', function() { doSearch(); });
});

function viewRecord(id) {
    window.location.href = '/medical-cases/' + id;
}

function exportPdf(id) {
    window.open('/medical-cases/' + id + '/export-pdf', '_blank');
}

function clearCustomFilters() {
    $('#quickSearch').val('');
    $('#filter_status').val('');
    $('#filter_doctor').val(null).trigger('change');
    $('#filter_patient').val(null).trigger('change');
    $('#filter_start_date').val('');
    $('#filter_end_date').val('');
}
