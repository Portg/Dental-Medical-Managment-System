/* Billing Report — payments + procedures tabs */
var activeTab = window.BillingReportConfig.activeTab;
var paymentTable, procedureTable;

$(document).ready(function() {
    initPaymentsTable();
    initProceduresTable();

    $('.billing-tab-btn').on('click', function(e) {
        e.preventDefault();
        switchTab($(this).data('tab'));
    });

    initDates();
    initProcDates();
    $('#customFilterBtn').on('click', function() { paymentTable.ajax.reload(); });
    $('#proc_search_btn').on('click', function() { procedureTable.ajax.reload(); });
    $('#proc_period_selector').on('change', initProcDates);
    $('#period_selector').on('change', initDates);
});

function initPaymentsTable() {
    paymentTable = $('#billingTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: window.BillingReportConfig.dataUrl,
            data: function(d) {
                d.tab = 'payments';
                d.start_date = $('.start_date').val();
                d.end_date = $('.end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'payment_date' },
            { data: 'patient' },
            { data: 'amount' },
            { data: 'payment_method' }
        ]
    });
}

function initProceduresTable() {
    procedureTable = $('#procedureTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: window.BillingReportConfig.dataUrl,
            data: function(d) {
                d.tab = 'procedures';
                d.start_date = $('#proc_start_date').val();
                d.end_date = $('#proc_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'procedure' },
            { data: 'procedure_income' }
        ]
    });
}

function switchTab(tab) {
    activeTab = tab;
    $('.billing-tab-btn').each(function() {
        var isActive = $(this).data('tab') === tab;
        $(this).css({ 'color': isActive ? '#1A237E' : '#666', 'border-bottom-color': isActive ? '#1A237E' : 'transparent' });
        $(this).closest('li').toggleClass('active', isActive);
    });
    if (tab === 'payments') {
        $('#payments-filters').show(); $('#procedures-filters').hide();
        $('#billingTable').closest('.dataTables_wrapper').show();
        $('#procedureTableWrapper').hide();
        $('#export-btn-area').html('<a href="' + window.BillingReportConfig.exportPaymentsUrl + '" class="text-danger"><i class="icon-cloud-download"></i> ' + LanguageManager.trans('report.download_excel_report') + '</a>');
    } else {
        $('#payments-filters').hide(); $('#procedures-filters').show();
        $('#billingTable').closest('.dataTables_wrapper').hide();
        $('#procedureTableWrapper').show();
        $('#export-btn-area').html('<a href="' + window.BillingReportConfig.exportProceduresUrl + '" class="text-danger"><i class="icon-cloud-download"></i> ' + LanguageManager.trans('report.download_excel_report') + '</a>');
    }
}

function initDates() {
    var dates = getPeriodDates($('#period_selector').val());
    $('.start_date').val(dates[0]); $('.end_date').val(dates[1]);
}
function initProcDates() {
    var dates = getPeriodDates($('#proc_period_selector').val());
    $('#proc_start_date').val(dates[0]); $('#proc_end_date').val(dates[1]);
}
function getPeriodDates(p) {
    var today = todaysDate();
    if (p === 'Today') return [today, today];
    if (p === 'This Month') return [formatDate(thisMonth()), today];
    if (p === 'Last Month') return [formatDate(lastMonthStart()), formatDate(lastMonthEnd())];
    return [today, today];
}
function clearFilters() { $('.start_date').val(''); $('.end_date').val(''); }
