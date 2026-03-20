/**
 * Patient Demographics Report — page script
 * PHP values bridged via window.PatientDemographicsConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.PatientDemographicsConfig || {};

    var ageData    = cfg.ageDistribution    || [];
    var genderData = cfg.genderDistribution || [];
    var sourceData = cfg.sourceDistribution || [];
    var trendData  = cfg.newPatientTrend    || [];

    var ageColors = ['#1A237E', '#283593', '#303F9F', '#3949AB', '#3F51B5', '#5C6BC0', '#7986CB', '#9FA8DA'];

    // Age distribution
    new Chart(document.getElementById('ageChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ageData.map(function (d) { return d.label; }),
            datasets: [{
                data: ageData.map(function (d) { return d.count; }),
                backgroundColor: ageColors.slice(0, ageData.length)
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Gender distribution
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: genderData.map(function (d) { return d.label; }),
            datasets: [{
                data: genderData.map(function (d) { return d.count; }),
                backgroundColor: ['#1A237E', '#E91E63', '#9E9E9E']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Source distribution
    var srcColors = ['#1A237E', '#3949AB', '#5C6BC0', '#7986CB', '#9FA8DA', '#C5CAE9', '#E8EAF6'];
    new Chart(document.getElementById('sourceChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: sourceData.map(function (d) { return d.source; }),
            datasets: [{
                data: sourceData.map(function (d) { return d.count; }),
                backgroundColor: srcColors.slice(0, sourceData.length)
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // New patient monthly trend
    new Chart(document.getElementById('newPatientTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: trendData.map(function (d) { return d.month; }),
            datasets: [{
                label: LanguageManager.trans('report.new_patients'),
                data: trendData.map(function (d) { return d.count; }),
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
