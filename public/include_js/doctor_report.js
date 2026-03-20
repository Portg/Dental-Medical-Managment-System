/* Doctor Report — performance DataTable + workload chart */
$(document).ready(function() {
    var cfg = window.DoctorReportConfig;
    $('.datepicker').datepicker({ language: cfg.locale, format: 'yyyy-mm-dd', autoclose: true });

    var perfTable = $('#performanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: cfg.dataUrl,
            data: function(d) {
                d.tab        = 'performance';
                d.doctor_id  = $('#perf_doctor_id').val();
                d.start_date = $('#perf_start_date').val();
                d.end_date   = $('#perf_end_date').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false },
            { data: 'created_at' },
            { data: 'patient' },
            { data: 'done_procedures_amount' },
            { data: 'invoice_amount' },
            { data: 'paid_amount' },
            { data: 'outstanding' }
        ]
    });

    var periods = {
        'Today':      [todaysDate(), todaysDate()],
        'Yesterday':  [yesterdaysDate(), yesterdaysDate()],
        'This week':  [thisWeek(), todaysDate()],
        'Last week':  [lastWeekStart(), lastWeekEnd()],
        'This Month': [formatDate(thisMonth()), todaysDate()],
        'Last Month': [formatDate(lastMonthStart()), formatDate(lastMonthEnd())]
    };

    $('#perf_period_selector').on('change', function() {
        var p = periods[$(this).val()];
        if (p) { $('#perf_start_date').val(p[0]); $('#perf_end_date').val(p[1]); }
    }).trigger('change');

    $('#perf_search_btn').on('click', function() { perfTable.ajax.reload(); });

    $('#wl_search_btn').on('click', function() {
        window.location.href = cfg.dataUrl + '?tab=workload&start_date=' + $('#wl_start_date').val() + '&end_date=' + $('#wl_end_date').val();
    });

    if (cfg.dailyTrend) {
        var trendData = cfg.dailyTrend;
        var colors = ['#1A237E','#E91E63','#4CAF50','#FF9800','#9C27B0','#00BCD4','#795548','#607D8B'];
        var datasets = [];
        trendData.doctors.forEach(function(doctor, i) {
            datasets.push({
                label: doctor,
                data: trendData.dates.map(function(date) {
                    return (trendData.data[date] && trendData.data[date][doctor]) || 0;
                }),
                borderColor: colors[i % colors.length],
                backgroundColor: 'transparent',
                tension: 0.3, borderWidth: 2
            });
        });
        new Chart(document.getElementById('dailyWorkloadChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: trendData.dates.map(function(d) { return d.substring(5); }),
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { position: 'top' } }
            }
        });
    }
});
