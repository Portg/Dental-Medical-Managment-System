/**
 * Daily Expenses Report — page script
 * PHP values bridged via window.DailyExpensesConfig (set in Blade).
 */
$(function () {
    var cfg = window.DailyExpensesConfig || {};

    dataTable = $('#sample_1').DataTable({
        language: LanguageManager.getDataTableLang(),
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.ajaxUrl || '',
            data: function (d) {
                d.search = $('input[type="search"]').val();
            }
        },
        dom: 'rtip',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', visible: true },
            { data: 'created_date', name: 'created_date' },
            { data: 'name', name: 'name' },
            { data: 'amount', name: 'amount' },
            { data: 'added_by', name: 'added_by' }
        ]
    });

    setupEmptyStateHandler();
});
