/**
 * Procedures Income Report — page script
 * PHP values bridged via window.ProceduresIncomeReportConfig (set in Blade).
 */
function default_todays_data() {
    $('.start_date').val(formatDate(thisMonth()));
    $('.end_date').val(todaysDate());
    $('#period_selector').val('This Month');
}

function clearCustomFilters() {
    default_todays_data();
}

$('#period_selector').on('change', function () {
    switch (this.value) {
        case 'Today':
            $('.start_date').val(todaysDate());
            $('.end_date').val(todaysDate());
            break;
        case 'Yesterday':
            $('.start_date').val(YesterdaysDate());
            $('.end_date').val(YesterdaysDate());
            break;
        case 'This week':
            $('.start_date').val(thisWeek());
            $('.end_date').val(todaysDate());
            break;
        case 'Last week':
            lastWeek();
            break;
        case 'This Month':
            $('.start_date').val(formatDate(thisMonth()));
            $('.end_date').val(todaysDate());
            break;
        case 'Last Month':
            lastMonth();
            break;
    }
});

$(function () {
    var cfg = window.ProceduresIncomeReportConfig || {};
    default_todays_data();

    dataTable = $('#payment-report').DataTable({
        language: LanguageManager.getDataTableLang(),
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.ajaxUrl || '',
            data: function (d) {
                d.start_date = $('.start_date').val();
                d.end_date   = $('.end_date').val();
                d.search     = $('input[type="search"]').val();
            }
        },
        dom: 'rtip',
        columns: [
            { data: 'DT_RowIndex',       name: 'DT_RowIndex',       visible: true },
            { data: 'procedure',         name: 'procedure' },
            { data: 'procedure_income',  name: 'procedure_income' }
        ]
    });

    setupEmptyStateHandler();
});

$('#customFilterBtn').click(function () {
    dataTable.draw(true);
});
