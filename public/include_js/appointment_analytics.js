/**
 * Appointment Analytics Report — page script
 * PHP values are bridged via window.AppointmentAnalyticsConfig (set in Blade).
 * Translations are loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.AppointmentAnalyticsConfig || {};

    // Date pickers
    $('.datepicker').datepicker({
        language: cfg.locale || 'zh-CN',
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // Select2 for source dropdown and tag multi-select
    if ($.fn.select2) {
        $('#filter-source').select2({ width: '100%' });
        $('#filter-tags').select2({
            width: '100%',
            placeholder: LanguageManager.trans('report.filter_tags')
        });
    }

    // ---------- Charts ----------

    // Daily appointment trend
    if (cfg.dailyTrendDates && cfg.dailyTrendCounts) {
        new Chart(document.getElementById('dailyTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: cfg.dailyTrendDates,
                datasets: [{
                    label: LanguageManager.trans('report.appointments_count'),
                    data: cfg.dailyTrendCounts,
                    borderColor: '#1A237E',
                    backgroundColor: 'rgba(26, 35, 126, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Peak hours distribution
    if (cfg.peakHoursData) {
        var hourLabels = [];
        var hourValues = [];
        var hourSuffix = LanguageManager.trans('report.hour_suffix');
        for (var h = 8; h <= 20; h++) {
            hourLabels.push(h + hourSuffix);
            hourValues.push(cfg.peakHoursData[h] || 0);
        }
        new Chart(document.getElementById('peakHoursChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [{
                    data: hourValues,
                    backgroundColor: '#3949AB'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Source distribution doughnut
    if (cfg.sourceData && cfg.sourceData.length > 0) {
        var sourceColors = ['#1A237E', '#3949AB', '#5C6BC0', '#7986CB', '#9FA8DA', '#C5CAE9', '#E8EAF6'];
        new Chart(document.getElementById('sourceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: cfg.sourceData.map(function (d) { return d.source; }),
                datasets: [{
                    data: cfg.sourceData.map(function (d) { return d.count; }),
                    backgroundColor: sourceColors.slice(0, cfg.sourceData.length)
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Chair utilization
    if (cfg.chairData && cfg.chairData.length > 0) {
        new Chart(document.getElementById('chairChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: cfg.chairData.map(function (d) { return d.chair; }),
                datasets: [{
                    label: LanguageManager.trans('report.appointments_count'),
                    data: cfg.chairData.map(function (d) { return d.count; }),
                    backgroundColor: '#3949AB'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
