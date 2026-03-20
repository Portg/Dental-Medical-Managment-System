/* Financial Calendar — FullCalendar monthly income/expense view */
$(document).ready(function() {
    var cfg = window.FinancialCalendarConfig;
    var calendarEl = document.getElementById('financialCalendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: cfg.locale,
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            var date = new Date(fetchInfo.start);
            date.setDate(date.getDate() + 7);
            $.get(cfg.dataUrl, {
                year:  date.getFullYear(),
                month: date.getMonth() + 1
            }, successCallback).fail(failureCallback);
        },
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var typeLabels = {
                income:  LanguageManager.trans('report.income'),
                expense: LanguageManager.trans('report.expenditure'),
                refund:  LanguageManager.trans('report.refund_amount'),
                net:     LanguageManager.trans('report.net_amount')
            };
            var typeLabel = typeLabels[props.type] || props.type;
            var amount = '¥' + parseFloat(props.amount).toLocaleString('zh-CN', { minimumFractionDigits: 2 });
            alert(info.event.startStr + '\n' + typeLabel + ': ' + amount);
        }
    });
    calendar.render();
});
