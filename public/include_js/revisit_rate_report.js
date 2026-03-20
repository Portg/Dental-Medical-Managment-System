/**
 * Revisit Rate Report — page script
 * PHP values bridged via window.RevisitRateConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.RevisitRateConfig || {};

    $('.datepicker').datepicker({
        language: cfg.locale || 'zh-CN',
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // Monthly revisit trend
    new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: cfg.trendLabels || [],
            datasets: [{
                label: LanguageManager.trans('report.revisit_rate'),
                data: cfg.trendRates || [],
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Revisit interval distribution
    new Chart(document.getElementById('intervalChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: cfg.intervalLabels || [],
            datasets: [{
                data: cfg.intervalCounts || [],
                backgroundColor: ['#4CAF50', '#8BC34A', '#FFC107', '#FF9800', '#FF5722', '#F44336']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
