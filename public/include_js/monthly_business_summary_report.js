/**
 * Monthly Business Summary Report — page script
 * PHP values bridged via window.MonthlyBusinessSummaryConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.MonthlyBusinessSummaryConfig || {};
    var revenueData = cfg.revenueByDay || [];

    new Chart(document.getElementById('dailyRevenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueData.map(function (d) { return d.date.substring(5); }),
            datasets: [{
                label: LanguageManager.trans('report.revenue'),
                data: revenueData.map(function (d) { return d.revenue; }),
                backgroundColor: 'rgba(26, 35, 126, 0.6)',
                borderColor: '#1A237E',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: function (v) { return '\u00A5' + v.toLocaleString(); } }
                }
            }
        }
    });
});
