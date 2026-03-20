/**
 * Patient Source Report — page script
 * PHP values bridged via window.PatientSourceReportConfig (set in Blade).
 * Translations loaded via LanguageManager.loadFromPHP() (set in Blade).
 */
$(document).ready(function () {
    var cfg = window.PatientSourceReportConfig || {};

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    var sourceNames  = cfg.sourceNames  || [];
    var sourceCounts = cfg.sourceCounts || [];
    var sourceColors = cfg.sourceColors || [];

    if (sourceNames.length > 0) {
        new Chart(document.getElementById('sourcePieChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: sourceNames,
                datasets: [{
                    data: sourceCounts,
                    backgroundColor: sourceColors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 10 }
                    }
                }
            }
        });
    }
});
