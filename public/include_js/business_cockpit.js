/**
 * Business Cockpit — page script
 * PHP values bridged via window.BusinessCockpitConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.BusinessCockpitConfig || {};

    var revenueTrend     = cfg.revenueTrend     || [];
    var paymentMix       = cfg.paymentMix       || [];
    var completionTrend  = cfg.completionTrend  || [];
    var doctorRanking    = cfg.doctorRanking    || [];

    // Revenue Trend (bar)
    new Chart(document.getElementById('revenueTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueTrend.map(function (d) { return d.date.substring(5); }),
            datasets: [{
                label: LanguageManager.trans('cockpit.daily_revenue'),
                data: revenueTrend.map(function (d) { return +(d.revenue / 1000).toFixed(2); }),
                backgroundColor: 'rgba(26, 35, 126, 0.6)',
                borderColor: '#1A237E',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return '\u00A5' + (ctx.raw * 1000).toLocaleString(); } } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: LanguageManager.trans('cockpit.unit_thousand') },
                    ticks: { callback: function (v) { return v.toLocaleString(); } }
                }
            }
        }
    });

    // Payment Mix (doughnut)
    var mixColors = {
        'Cash': '#3598DC', 'WeChat': '#09BB07', 'Alipay': '#1677FF',
        'BankCard': '#F5A623', 'StoredValue': '#8E44AD', 'Insurance': '#2ECC71',
        'Online Wallet': '#E67E22', 'Mobile Money': '#1ABC9C', 'Cheque': '#95A5A6',
        'Self Account': '#E74C3C', 'Credit': '#E74C3C'
    };
    var defaultColors = ['#5C6BC0', '#7986CB', '#9FA8DA', '#C5CAE9', '#E8EAF6', '#3949AB', '#1A237E', '#283593', '#303F9F', '#3F51B5'];
    new Chart(document.getElementById('paymentMixChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: paymentMix.map(function (d) { return d.payment_method; }),
            datasets: [{
                data: paymentMix.map(function (d) { return parseFloat(d.total); }),
                backgroundColor: paymentMix.map(function (d, i) {
                    return mixColors[d.payment_method] || defaultColors[i % defaultColors.length];
                })
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } },
                tooltip: { callbacks: {
                    label: function (ctx) {
                        return ctx.label + ': \u00A5' + (ctx.raw / 1000).toFixed(1) + LanguageManager.trans('cockpit.unit_thousand');
                    }
                }}
            }
        }
    });

    // Completion Trend (line)
    new Chart(document.getElementById('completionTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: completionTrend.map(function (d) { return d.date.substring(5); }),
            datasets: [{
                label: LanguageManager.trans('cockpit.completion_rate'),
                data: completionTrend.map(function (d) { return d.rate; }),
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
            }
        }
    });

    // Doctor Ranking (horizontal bar)
    new Chart(document.getElementById('doctorRankingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: doctorRanking.map(function (d) { return d.doctor_name; }),
            datasets: [{
                label: LanguageManager.trans('cockpit.revenue'),
                data: doctorRanking.map(function (d) { return +(parseFloat(d.revenue) / 1000).toFixed(2); }),
                backgroundColor: '#3949AB'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return '\u00A5' + (ctx.raw * 1000).toLocaleString(); } } }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: LanguageManager.trans('cockpit.unit_thousand') },
                    ticks: { callback: function (v) { return v.toLocaleString(); } }
                }
            }
        }
    });
});
