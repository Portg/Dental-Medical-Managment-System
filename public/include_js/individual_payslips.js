$(function () {
    $('#sample_1').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: window.IndividualPayslipsConfig.ajaxUrl,
            data: function (d) {
            }
        },
        dom: 'Bfrtip',
        buttons: {
            buttons: [
                // {extend: 'pdfHtml5', className: 'pdfButton'},
                // {extend: 'excelHtml5', className: 'excelButton'},
            ]
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'payslip_month', name: 'payslip_month'},
            {data: 'basic_salary', name: 'basic_salary'},
            {data: 'total_advances', name: 'total_advances'},
            {data: 'total_allowances', name: 'total_allowances'},
            {data: 'total_deductions', name: 'total_deductions'},
            {data: 'due_balance', name: 'due_balance'}
        ]
    });
});
