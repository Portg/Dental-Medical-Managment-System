/**
 * Doctor Workload Report — page script
 * PHP values bridged via window.DoctorWorkloadConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.DoctorWorkloadConfig || {};

    $('.datepicker').datepicker({
        language: cfg.locale || 'zh-CN',
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    var trendData    = cfg.dailyTrend   || { dates: [], doctors: [], data: {} };
    var doctorData   = cfg.doctorStats  || [];
    var colors = ['#1A237E', '#E91E63', '#4CAF50', '#FF9800', '#9C27B0', '#00BCD4', '#795548', '#607D8B'];

    // Daily workload trend (line per doctor)
    var datasets = [];
    (trendData.doctors || []).forEach(function (doctor, i) {
        datasets.push({
            label: doctor,
            data: (trendData.dates || []).map(function (date) {
                return (trendData.data[date] && trendData.data[date][doctor]) || 0;
            }),
            borderColor: colors[i % colors.length],
            backgroundColor: 'transparent',
            tension: 0.3,
            borderWidth: 2
        });
    });

    new Chart(document.getElementById('dailyWorkloadChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: (trendData.dates || []).map(function (d) { return d.substring(5); }),
            datasets: datasets
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            plugins: { legend: { position: 'top' } }
        }
    });

    // Doctor ranking stacked bar
    new Chart(document.getElementById('doctorRankingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: doctorData.map(function (d) { return d.doctor_name; }),
            datasets: [{
                label: LanguageManager.trans('report.completed'),
                data: doctorData.map(function (d) { return d.completed; }),
                backgroundColor: '#2E7D32'
            }, {
                label: LanguageManager.trans('report.cancelled'),
                data: doctorData.map(function (d) { return d.cancelled; }),
                backgroundColor: '#E65100'
            }, {
                label: LanguageManager.trans('report.no_show'),
                data: doctorData.map(function (d) { return d.no_show; }),
                backgroundColor: '#C62828'
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: { legend: { position: 'top' } }
        }
    });
});
