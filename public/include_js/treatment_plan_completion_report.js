/**
 * Treatment Plan Completion Report — page script
 * PHP values bridged via window.TreatmentPlanCompletionConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.TreatmentPlanCompletionConfig || {};

    $('.datepicker').datepicker({
        language: cfg.locale || 'zh-CN',
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    var trendData  = cfg.monthlyTrend || [];
    var doctorData = cfg.byDoctor     || [];

    // Monthly conversion trend (dual axis)
    new Chart(document.getElementById('monthlyTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: trendData.map(function (d) { return d.month; }),
            datasets: [{
                label: LanguageManager.trans('report.total_quotations'),
                data: trendData.map(function (d) { return d.total; }),
                backgroundColor: 'rgba(26, 35, 126, 0.3)',
                borderColor: '#1A237E',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: LanguageManager.trans('report.converted_count'),
                data: trendData.map(function (d) { return d.converted; }),
                backgroundColor: 'rgba(46, 125, 50, 0.3)',
                borderColor: '#2E7D32',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: LanguageManager.trans('report.conversion_rate'),
                data: trendData.map(function (d) { return d.rate; }),
                type: 'line',
                borderColor: '#E65100',
                backgroundColor: 'transparent',
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, position: 'left', ticks: { stepSize: 1 } },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    max: 100,
                    grid: { drawOnChartArea: false },
                    ticks: { callback: function (v) { return v + '%'; } }
                }
            }
        }
    });

    // Doctor conversion rate (horizontal bar)
    if (doctorData.length > 0) {
        new Chart(document.getElementById('doctorConversionChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: doctorData.map(function (d) { return d.doctor_name; }),
                datasets: [{
                    label: LanguageManager.trans('report.conversion_rate'),
                    data: doctorData.map(function (d) { return d.conversion_rate; }),
                    backgroundColor: doctorData.map(function (d) {
                        return d.conversion_rate >= 60 ? '#2E7D32' : (d.conversion_rate >= 30 ? '#E65100' : '#C62828');
                    })
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
                }
            }
        });
    }
});
