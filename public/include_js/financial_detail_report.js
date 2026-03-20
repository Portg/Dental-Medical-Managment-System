/* Financial Detail Report — payments / refunds / expenses / employee tabs */
$(document).ready(function() {
    var locale = window.FinancialDetailConfig.locale;
    $('.datepicker').datepicker({ language: locale, format: 'yyyy-mm-dd', autoclose: true });

    var today = new Date().toISOString().split('T')[0];
    var monthStart = today.substring(0, 8) + '01';
    $('#pay_start_date, #ref_start_date, #exp_start_date, #emp_start_date').val(monthStart);
    $('#pay_end_date, #ref_end_date, #exp_end_date, #emp_end_date').val(today);

    var dataUrl = window.FinancialDetailConfig.dataUrl;

    var payTable = $('#paymentsTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: dataUrl,
            data: function(d) {
                d.tab = 'payments';
                d.start_date = $('#pay_start_date').val();
                d.end_date   = $('#pay_end_date').val();
                d.payment_type = $('#pay_type').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'payment_date' },
            { data: 'invoice_no' },
            { data: 'patient' },
            { data: 'payment_type' },
            { data: 'amount_fmt', className: 'text-right amount-col' },
            { data: 'cashier_name' }
        ]
    });
    $('#pay_search_btn').on('click', function() { payTable.ajax.reload(); });

    var refTable = $('#refundsTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: dataUrl,
            data: function(d) {
                d.tab = 'refunds';
                d.start_date = $('#ref_start_date').val();
                d.end_date   = $('#ref_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'refund_date' },
            { data: 'invoice_no' },
            { data: 'patient' },
            { data: 'amount_fmt', className: 'text-right refund-amount' },
            { data: 'reason', defaultContent: '' },
            { data: 'operator_name' }
        ]
    });
    $('#ref_search_btn').on('click', function() { refTable.ajax.reload(); });

    var expTable = $('#expensesTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: dataUrl,
            data: function(d) {
                d.tab = 'expenses';
                d.start_date = $('#exp_start_date').val();
                d.end_date   = $('#exp_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'payment_date' },
            { data: 'description', defaultContent: '' },
            { data: 'supplier_name' },
            { data: 'amount_fmt', className: 'text-right expense-amount' },
            { data: 'operator_name' }
        ]
    });
    $('#exp_search_btn').on('click', function() { expTable.ajax.reload(); });

    var empTable = $('#employeeTable').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: dataUrl,
            data: function(d) {
                d.tab = 'employee';
                d.start_date = $('#emp_start_date').val();
                d.end_date   = $('#emp_end_date').val();
                d.cashier_id = $('#emp_cashier').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'payment_date' },
            { data: 'invoice_no' },
            { data: 'patient' },
            { data: 'payment_type' },
            { data: 'amount_fmt', className: 'text-right amount-col' },
            { data: 'cashier_name' }
        ]
    });
    $('#emp_search_btn').on('click', function() { empTable.ajax.reload(); });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var tabId = $(e.target).attr('href');
        if (tabId === '#tab-payments') payTable.columns.adjust().draw(false);
        if (tabId === '#tab-refunds')  refTable.columns.adjust().draw(false);
        if (tabId === '#tab-expenses') expTable.columns.adjust().draw(false);
        if (tabId === '#tab-employee') empTable.columns.adjust().draw(false);
    });
});
