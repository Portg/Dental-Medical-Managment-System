/* Unpaid Invoices Report */
$(document).ready(function() {
    var cfg = window.UnpaidInvoicesConfig;
    $('.datepicker').datepicker({ language: cfg.locale, format: 'yyyy-mm-dd', autoclose: true });

    var today = new Date().toISOString().split('T')[0];
    var monthStart = today.substring(0, 8) + '01';
    $('#start_date').val(monthStart);
    $('#end_date').val(today);

    var statusClass = { 'unpaid': 'label-danger', 'partial': 'label-warning', 'overdue': 'label-default' };

    var table = $('#unpaidTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.dataUrl,
            data: function(d) {
                d.start_date     = $('#start_date').val();
                d.end_date       = $('#end_date').val();
                d.payment_status = $('#payment_status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'invoice_no' },
            { data: 'patient_name' },
            { data: 'phone_no',         defaultContent: '' },
            { data: 'invoice_date',     defaultContent: '' },
            { data: 'due_date',         defaultContent: '' },
            { data: 'total_amount_fmt', className: 'text-right' },
            { data: 'paid_amount_fmt',  className: 'text-right' },
            { data: 'outstanding_fmt',  className: 'text-right outstanding-amount' },
            {
                data: 'payment_status',
                render: function(val) {
                    var cls = statusClass[val] || 'label-default';
                    return '<span class="label ' + cls + '">' + (cfg.statusLabels[val] || val) + '</span>';
                }
            },
            { data: 'doctor_name', defaultContent: '' }
        ],
        drawCallback: function() {
            var total = this.api().page.info().recordsTotal;
            $('#card-count').text(total);
            $('#summary-cards').show();
        }
    });

    $('#search_btn').on('click', function() { table.ajax.reload(); });
});
