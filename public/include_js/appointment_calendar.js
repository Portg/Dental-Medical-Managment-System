document.addEventListener('DOMContentLoaded', function() {
    var appointmentStatusList = window._appointmentStatusList || [];
    var calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        var _cs = window._clinicSettings || {};
        var _slotMin = parseInt(_cs.slot_interval, 10) || 30;
        var _slotDur = '00:' + (_slotMin < 10 ? '0' + _slotMin : '' + _slotMin) + ':00';

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: window._calLocale || 'zh-cn',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            slotDuration: _slotDur,
            slotMinTime: _cs.start_time || '08:30',
            slotMaxTime: _cs.end_time   || '18:30',
            scrollTime:  _cs.start_time || '08:30',
            allDaySlot: false,
            selectOverlap: false,
            editable: false,
            selectable: true,
            selectMirror: true,
            eventDisplay: 'block',
            events: {
                url: window._appRoutes.calendarEvents,
                method: 'GET',
                failure: function() {
                    console.error('Failed to load calendar events');
                }
            },
            select: function(info) {
                var prefill = { date: info.startStr.substring(0, 10) };
                if (info.view.type !== 'dayGridMonth') {
                    prefill.time = info.startStr.substring(11, 16);
                    var durationMs = info.end - info.start;
                    prefill.duration = Math.round(durationMs / 60000);
                }
                if (typeof openAppointmentDrawer === 'function') {
                    openAppointmentDrawer(prefill);
                }
                calendar.unselect();
            },
            dateClick: function(info) {
                if (info.view.type === 'listWeek') {
                    if (typeof openAppointmentDrawer === 'function') {
                        openAppointmentDrawer({ date: info.dateStr.substring(0, 10) });
                    }
                }
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                showAppointmentPopover(info.event, info.jsEvent);
            }
        });
        window._appointmentCalendar = calendar;

        // --- Appointment Popover Logic (shared by calendar & resource grid) ---
        var $popover = $('#apt-popover');
        window._aptPopoverEventId = null;

        function showAppointmentPopover(event, jsEvent) {
            var ep = event.extendedProps;
            window._aptPopoverEventId = event.id;

            $('#apt-popover-patient').text(ep.patient_name || '');
            $('#apt-popover-phone').text(ep.patient_phone || '');
            $('#apt-popover-time').text((ep.start_time || '') + ' - ' + (ep.end_time || ''));
            $('#apt-popover-doctor').text(ep.doctor_name || '');
            $('#apt-popover-service').text(ep.service_name || '-');
            var $statusSelect = $('#apt-popover-status-select');
            $statusSelect.empty();
            appointmentStatusList.forEach(function(item) {
                var opt = $('<option></option>').val(item.code).text(item.name);
                if (item.code === ep.status_code) opt.prop('selected', true);
                $statusSelect.append(opt);
            });

            if (typeof ep.notes !== 'undefined' && ep.notes) {
                $('#apt-popover-notes').text(ep.notes);
                $('#apt-popover-notes-row').show();
            } else {
                $('#apt-popover-notes-row').hide();
            }

            var x = jsEvent.pageX, y = jsEvent.pageY;
            $popover.css({ top: y + 8, left: x + 8, display: 'block' });

            setTimeout(function() {
                var pw = $popover.outerWidth(), ph = $popover.outerHeight();
                var ww = $(window).width(), wh = $(window).height();
                var st = $(window).scrollTop(), sl = $(window).scrollLeft();
                if (x + 8 + pw > sl + ww) $popover.css('left', x - pw - 8);
                if (y + 8 + ph > st + wh) $popover.css('top', y - ph - 8);
            }, 0);
        }
        window.showAppointmentPopover = showAppointmentPopover;

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#apt-popover, .fc-event, .drg-event').length) {
                $popover.hide();
                window._aptPopoverEventId = null;
            }
        });

        $('#apt-popover-edit').on('click', function() {
            var eid = window._aptPopoverEventId;
            $popover.hide();
            if (eid && typeof editRecord === 'function') editRecord(eid);
        });
        $('#apt-popover-reschedule').on('click', function() {
            var eid = window._aptPopoverEventId;
            $popover.hide();
            if (eid && typeof RescheduleAppointment === 'function') RescheduleAppointment(eid);
        });
        $('#apt-popover-delete').on('click', function() {
            var eid = window._aptPopoverEventId;
            $popover.hide();
            if (eid && typeof deleteRecord === 'function') deleteRecord(eid);
        });
        $('#apt-popover-sms').on('click', function() {
            var eid = window._aptPopoverEventId;
            if (!eid) return;
            var phone = $('#apt-popover-phone').text();
            if (!phone) { toastr.warning(LanguageManager.trans('appointment.no_phone_for_sms')); return; }
            $popover.hide();
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: window._appRoutes.appointments + '/' + eid + '/send-reminder',
                data: { _token: CSRF_TOKEN },
                success: function(res) {
                    if (res.status) toastr.success(res.message);
                    else toastr.error(res.message);
                },
                error: function() { toastr.error(LanguageManager.trans('common.error')); }
            });
        });
        $('#apt-popover-status-select').on('change', function() {
            var eid = window._aptPopoverEventId;
            if (!eid) return;
            var newStatus = $(this).val();
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                type: 'POST',
                url: window._appRoutes.appointmentStatus,
                data: { _token: CSRF_TOKEN, appointment_id: eid, appointment_status: newStatus },
                success: function(res) {
                    if (res.status) {
                        toastr.success(LanguageManager.trans('appointment.status_updated'));
                        calendar.refetchEvents();
                        if (typeof window._doctorDayViewCalendar !== 'undefined' && window._doctorDayViewCalendar) {
                            window._doctorDayViewCalendar.refetchEvents();
                        }
                    } else {
                        toastr.error(res.message || LanguageManager.trans('common.error'));
                    }
                },
                error: function() { toastr.error(LanguageManager.trans('common.error')); }
            });
        });
        // Render calendar when tab is shown
        $('a[href="#appointment_calender_tab"]').on('shown.bs.tab', function () {
            calendar.render();
        });
    }

    // Doctor day view tab initialization
    $(function() {
        $('a[href="#doctor_day_view_tab"]').on('shown.bs.tab', function () {
            if (window._drgInstance) window._drgInstance.render();
        });
    });
});
