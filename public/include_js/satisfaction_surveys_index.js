$(document).ready(function () {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // 月度趋势图
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: window.SatisfactionSurveysConfig.trendLabels,
            datasets: [{
                label: LanguageManager.trans('satisfaction.avg_rating'),
                data: window.SatisfactionSurveysConfig.trendRatings,
                borderColor: '#1A237E',
                backgroundColor: 'rgba(26, 35, 126, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            }, {
                label: 'NPS',
                data: window.SatisfactionSurveysConfig.trendNps,
                borderColor: '#4CAF50',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.3,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {mode: 'index', intersect: false},
            scales: {
                y: {beginAtZero: true, max: 5, position: 'left'},
                y1: {beginAtZero: false, min: -100, max: 100, position: 'right', grid: {drawOnChartArea: false}}
            },
            plugins: {legend: {position: 'bottom'}}
        }
    });

    // DataTable
    $('#surveysDataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.SatisfactionSurveysConfig.dataUrl,
            data: function (d) {
                d.start_date = window.SatisfactionSurveysConfig.startDate;
                d.end_date = window.SatisfactionSurveysConfig.endDate;
            }
        },
        columns: [
            {data: 'patient_name', name: 'patient_name'},
            {data: 'doctor_name', name: 'doctor_name'},
            {data: 'survey_date_formatted', name: 'survey_date'},
            {data: 'ratings_display', name: 'overall_rating'},
            {data: 'status_badge', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });

    // 批量发送
    $('#sendBatchForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: window.SatisfactionSurveysConfig.sendBatchUrl,
            method: 'POST',
            data: $(this).serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function (res) {
                $('#sendBatchModal').modal('hide');
                toastr.success(res.message);
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : LanguageManager.trans('common.error'));
            }
        });
    });
});
