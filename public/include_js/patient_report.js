/* Patient Report — source analysis + demographics charts */
$(document).ready(function() {
    var cfg = window.PatientReportConfig;
    $('.datepicker').datepicker({ language: cfg.locale, format: 'yyyy-mm-dd', autoclose: true });

    if (cfg.sourceAnalysis) {
        var ctx = document.getElementById('sourcePieChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels:   cfg.sourceAnalysis.map(function(d) { return d.name; }),
                datasets: [{ data: cfg.sourceAnalysis.map(function(d) { return d.patient_count; }), backgroundColor: cfg.sourceAnalysis.map(function(d) { return d.color; }), borderWidth: 0 }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } } }
        });
    }

    if (cfg.ageDistribution) {
        var ageColors = ['#1A237E','#283593','#303F9F','#3949AB','#3F51B5','#5C6BC0','#7986CB','#9FA8DA'];
        new Chart(document.getElementById('ageChart').getContext('2d'), {
            type: 'bar',
            data: { labels: cfg.ageDistribution.map(function(d){return d.label;}), datasets: [{ data: cfg.ageDistribution.map(function(d){return d.count;}), backgroundColor: ageColors.slice(0, cfg.ageDistribution.length) }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('genderChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels: cfg.genderDistribution.map(function(d){return d.label;}), datasets: [{ data: cfg.genderDistribution.map(function(d){return d.count;}), backgroundColor: ['#1A237E','#E91E63','#9E9E9E'] }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        var srcColors = ['#1A237E','#3949AB','#5C6BC0','#7986CB','#9FA8DA','#C5CAE9','#E8EAF6'];
        new Chart(document.getElementById('sourceDistChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels: cfg.sourceDistribution.map(function(d){return d.source;}), datasets: [{ data: cfg.sourceDistribution.map(function(d){return d.count;}), backgroundColor: srcColors.slice(0, cfg.sourceDistribution.length) }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('newPatientTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: cfg.newPatientTrend.map(function(d){return d.month;}),
                datasets: [{ label: LanguageManager.trans('report.new_patients'), data: cfg.newPatientTrend.map(function(d){return d.count;}), borderColor: '#1A237E', backgroundColor: 'rgba(26,35,126,0.1)', fill: true, tension: 0.3 }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
});
