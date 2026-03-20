/**
 * Debtors Report — page script
 * PHP values bridged via window.DebtorsReportConfig (set in Blade).
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
    var cfg = window.DebtorsReportConfig || {};
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
            }
        },
        dom: 'rtip',
        columns: [
            { data: 'invoice_no',          name: 'invoice_no' },
            { data: 'invoice_date',         name: 'invoice_date' },
            { data: 'surname',              name: 'surname' },
            { data: 'othername',            name: 'othername' },
            { data: 'phone_no',             name: 'phone_no' },
            { data: 'invoice_amount',       name: 'invoice_amount' },
            { data: 'amount_paid',          name: 'amount_paid' },
            { data: 'outstanding_balance',  name: 'outstanding_balance' }
        ]
    });

    setupEmptyStateHandler();
});

$('#customFilterBtn').click(function () {
    dataTable.draw(true);
});
