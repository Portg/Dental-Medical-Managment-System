/* Lab Case Statistics Report — status pie + monthly trend charts */
$(document).ready(function() {
    var cfg = window.LabStatisticsConfig;
    $('.datepicker').datepicker({ language: cfg.locale, format: 'yyyy-mm-dd', autoclose: true });

    if (cfg.byStatus && cfg.byStatus.length > 0) {
        var statusLabels = {
            pending:       LanguageManager.trans('lab_cases.status_pending'),
            sent:          LanguageManager.trans('lab_cases.status_sent'),
            in_production: LanguageManager.trans('lab_cases.status_in_production'),
            returned:      LanguageManager.trans('lab_cases.status_returned'),
            try_in:        LanguageManager.trans('lab_cases.status_try_in'),
            completed:     LanguageManager.trans('lab_cases.status_completed'),
            rework:        LanguageManager.trans('lab_cases.status_rework')
        };
        new Chart(document.getElementById('statusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: cfg.byStatus.map(function(d) { return statusLabels[d.status] || d.status; }),
                datasets: [{
                    data: cfg.byStatus.map(function(d) { return d.count; }),
                    backgroundColor: ['#42A5F5','#66BB6A','#FFA726','#EF5350','#AB47BC','#26C6DA','#FF7043']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'right' } } }
        });
    }

    if (cfg.monthlyTrend && cfg.monthlyTrend.length > 0) {
        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cfg.monthlyTrend.map(function(d) { return d.month; }),
                datasets: [
                    {
                        label: LanguageManager.trans('report.total'),
                        data: cfg.monthlyTrend.map(function(d) { return d.total; }),
                        backgroundColor: 'rgba(26, 35, 126, 0.3)', borderColor: '#1A237E', borderWidth: 1
                    },
                    {
                        label: LanguageManager.trans('report.completed'),
                        data: cfg.monthlyTrend.map(function(d) { return d.completed; }),
                        backgroundColor: 'rgba(46, 125, 50, 0.3)', borderColor: '#2E7D32', borderWidth: 1
                    }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }
});
