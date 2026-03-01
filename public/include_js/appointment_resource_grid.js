/**
 * Doctor Resource Grid — custom time-grid with doctors as columns.
 * Depends on jQuery, LanguageManager (for translations).
 */
(function($) {
    'use strict';

    var cs = window._clinicSettings || {};
    var SLOT_HEIGHT   = 28;
    var SLOT_MINUTES  = 15;
    var START_HOUR    = parseInt(cs.grid_start_hour, 10) || 8;
    var END_HOUR      = parseInt(cs.grid_end_hour, 10) || 21;
    var TOTAL_SLOTS   = (END_HOUR - START_HOUR) * (60 / SLOT_MINUTES);

    function DoctorResourceGrid(options) {
        this.container     = $(options.container);
        this.doctorsUrl    = options.doctorsUrl    || '/appointments/doctors';
        this.eventsUrl     = options.eventsUrl     || '/appointments/calendar-events';
        this.currentDate   = new Date();
        this.currentDate.setHours(0,0,0,0);
        this.doctors       = [];
        this.events        = [];
        this._rendered     = false;

        this._bindToolbar();
        this._bindGridEvents();
        window._drgInstance = this;
    }

    DoctorResourceGrid.prototype._bindToolbar = function() {
        var self = this;
        $('#drg-prev').on('click', function()  { self._shiftDate(-1); });
        $('#drg-next').on('click', function()  { self._shiftDate(1); });
        $('#drg-today').on('click', function() {
            self.currentDate = new Date();
            self.currentDate.setHours(0,0,0,0);
            self._load();
        });
    };

    DoctorResourceGrid.prototype._shiftDate = function(days) {
        this.currentDate.setDate(this.currentDate.getDate() + days);
        this._load();
    };

    DoctorResourceGrid.prototype._formatDate = function(d) {
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth()+1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + dd;
    };

    DoctorResourceGrid.prototype._formatDisplay = function(d) {
        var weekdayKeys = [
            'appointment.weekday_sun', 'appointment.weekday_mon',
            'appointment.weekday_tue', 'appointment.weekday_wed',
            'appointment.weekday_thu', 'appointment.weekday_fri',
            'appointment.weekday_sat'
        ];
        var weekday = LanguageManager.trans(weekdayKeys[d.getDay()]);
        return this._formatDate(d) + '  ' + weekday;
    };

    DoctorResourceGrid.prototype.render = function() {
        if (!this._rendered) {
            this._rendered = true;
            this._load();
        }
    };

    DoctorResourceGrid.prototype._load = function() {
        var self = this;
        var dateStr = this._formatDate(this.currentDate);
        $('#drg-date-label').text(this._formatDisplay(this.currentDate));

        var nextDay = new Date(this.currentDate);
        nextDay.setDate(nextDay.getDate() + 1);
        var endStr = this._formatDate(nextDay);

        $.when(
            $.getJSON(this.doctorsUrl, { date: dateStr }),
            $.getJSON(this.eventsUrl, { start: dateStr, end: endStr })
        ).done(function(docRes, evtRes) {
            self.doctors = docRes[0] || docRes;
            self.events  = evtRes[0] || evtRes;
            self._buildGrid();
        }).fail(function() {
            self.container.html('<div class="drg-empty">' +
                LanguageManager.trans('common.error') + '</div>');
        });
    };

    DoctorResourceGrid.prototype._timeToSlot = function(timeStr) {
        var parts = timeStr.split(':');
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        return Math.round(((h - START_HOUR) * 60 + m) / SLOT_MINUTES);
    };

    /**
     * Round a time string (HH:MM) to the nearest 30-min slot boundary
     * so it matches the appointment drawer's time-slot grid.
     */
    DoctorResourceGrid.prototype._roundTo30 = function(timeStr) {
        var parts = timeStr.split(':');
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        m = m < 15 ? 0 : (m < 45 ? 30 : 60);
        if (m === 60) { h++; m = 0; }
        return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
    };

    /**
     * Check if a time (HH:MM) falls within a doctor's schedule range.
     * Returns true if in-schedule, false if out-of-schedule.
     * If no schedule exists for the doctor, returns null (unknown).
     */
    DoctorResourceGrid.prototype._isInSchedule = function(doctor, timeHHMM) {
        if (!doctor.schedule) return null;
        return timeHHMM >= doctor.schedule.start_time && timeHHMM < doctor.schedule.end_time;
    };

    DoctorResourceGrid.prototype._buildGrid = function() {
        var self = this;

        if (!this.doctors.length) {
            this.container.html('<div class="drg-empty">' +
                LanguageManager.trans('appointment.no_appointments') + '</div>');
            return;
        }

        // Group events by doctor_id
        var eventsByDoctor = {};
        var countByDoctor  = {};
        this.doctors.forEach(function(d) {
            eventsByDoctor[d.id] = [];
            countByDoctor[d.id]  = 0;
        });
        this.events.forEach(function(evt) {
            var did = evt.resourceId || (evt.extendedProps && evt.extendedProps.doctor_id);
            if (did && eventsByDoctor[did]) {
                eventsByDoctor[did].push(evt);
                countByDoctor[did]++;
            }
        });

        // Build HTML
        var html = '<table class="drg-table"><thead><tr>';
        html += '<th class="drg-time-col"></th>';
        this.doctors.forEach(function(d) {
            var scheduleLabel = '';
            if (d.schedule) {
                scheduleLabel = '<span class="drg-schedule-range">' +
                    d.schedule.start_time + '-' + d.schedule.end_time + '</span>';
            } else {
                scheduleLabel = '<span class="drg-no-schedule">' +
                    LanguageManager.trans('appointment.no_schedule') + '</span>';
            }
            html += '<th>' + self._esc(d.title) +
                '<span class="drg-doctor-count">(' + (countByDoctor[d.id] || 0) + ')</span>' +
                scheduleLabel + '</th>';
        });
        html += '</tr></thead><tbody>';

        for (var s = 0; s < TOTAL_SLOTS; s++) {
            var totalMin = START_HOUR * 60 + s * SLOT_MINUTES;
            var hh = ('0' + Math.floor(totalMin/60)).slice(-2);
            var mm = ('0' + (totalMin % 60)).slice(-2);
            var timeLabel = (s % (60/SLOT_MINUTES) === 0) ? hh + ':' + mm : '';
            var timeHHMM = hh + ':' + mm;

            html += '<tr>';
            html += '<td class="drg-time-cell">' + timeLabel + '</td>';
            this.doctors.forEach(function(d) {
                var inSchedule = self._isInSchedule(d, timeHHMM);
                var cellClass = 'drg-cell';
                if (inSchedule === false) {
                    cellClass += ' drg-off-schedule';
                } else if (inSchedule === null) {
                    cellClass += ' drg-no-schedule-cell';
                }
                html += '<td class="' + cellClass + '" data-slot="' + s +
                    '" data-doctor="' + d.id + '" data-time="' + timeHHMM + '"></td>';
            });
            html += '</tr>';
        }
        html += '</tbody></table>';

        this.container.html(html);

        // Place event blocks
        this.doctors.forEach(function(d, colIdx) {
            var col = colIdx + 1;
            eventsByDoctor[d.id].forEach(function(evt) {
                self._placeEvent(evt, col);
            });
        });

        // Now indicator
        this._placeNowLine();
    };

    /**
     * Bind event delegation once (not per _buildGrid call).
     */
    DoctorResourceGrid.prototype._bindGridEvents = function() {
        var self = this;

        // Cell click → open new appointment (rounded to 30-min for slot match)
        this.container.on('click', '.drg-cell', function(e) {
            if ($(e.target).closest('.drg-event').length) return;
            var $cell = $(this);

            if ($cell.hasClass('drg-off-schedule')) {
                toastr.warning(LanguageManager.trans('appointment.off_schedule_warning'));
                return;
            }

            var doctorId = $cell.data('doctor');
            var rawTime  = $cell.data('time');
            var time     = self._roundTo30(rawTime);
            if (typeof openAppointmentDrawer === 'function') {
                openAppointmentDrawer({
                    date: self._formatDate(self.currentDate),
                    doctor_id: doctorId,
                    time: time
                });
            }
        });

        // Event block click → reuse the shared popover
        this.container.on('click', '.drg-event', function(e) {
            e.stopPropagation();
            var evt = $(this).data('event');
            if (evt && typeof window.showAppointmentPopover === 'function') {
                var fakeEvent = {
                    id: evt.id,
                    backgroundColor: evt.backgroundColor || '#3a87ad',
                    extendedProps: evt.extendedProps || {}
                };
                window.showAppointmentPopover(fakeEvent, e);
            }
        });
    };

    DoctorResourceGrid.prototype._placeEvent = function(evt, colIdx) {
        var ep = evt.extendedProps || {};
        var startTime = ep.start_time || evt.start.substring(11, 16);
        var endTime   = ep.end_time   || evt.end.substring(11, 16);
        var startSlot = this._timeToSlot(startTime);
        var endSlot   = this._timeToSlot(endTime);
        if (startSlot < 0) startSlot = 0;
        if (endSlot > TOTAL_SLOTS) endSlot = TOTAL_SLOTS;
        var slotSpan = endSlot - startSlot;
        if (slotSpan < 1) slotSpan = 1;

        var topPx    = startSlot * SLOT_HEIGHT;
        var heightPx = slotSpan * SLOT_HEIGHT - 2;
        var bgColor  = evt.backgroundColor || '#3a87ad';
        var title    = ep.patient_name || evt.title || '';

        var $cell = this.container.find(
            'td.drg-cell[data-slot="' + startSlot + '"]'
        ).eq(colIdx - 1);

        if (!$cell.length) return;
        $cell.css('position', 'relative');

        var $block = $('<div class="drg-event"></div>')
            .css({
                top: 0,
                height: heightPx,
                backgroundColor: bgColor
            })
            .html(
                '<div class="drg-event-title">' + this._esc(title) + '</div>' +
                '<div class="drg-event-time">' + startTime + ' - ' + endTime + '</div>'
            )
            .data('event', evt);

        $cell.append($block);
    };

    DoctorResourceGrid.prototype._placeNowLine = function() {
        var now = new Date();
        var todayStr = this._formatDate(now);
        var gridDateStr = this._formatDate(this.currentDate);
        if (todayStr !== gridDateStr) return;

        var minutes = now.getHours() * 60 + now.getMinutes();
        var slotPos = (minutes - START_HOUR * 60) / SLOT_MINUTES;
        if (slotPos < 0 || slotPos > TOTAL_SLOTS) return;

        var topPx = slotPos * SLOT_HEIGHT;
        this.container.find('tbody').css('position', 'relative');
        this.container.find('tbody').append(
            '<div class="drg-now-line" style="top:' + topPx + 'px;"></div>'
        );
    };

    DoctorResourceGrid.prototype._esc = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    // Popover reuse: resource grid events use the same global popover
    // as the FullCalendar view (showAppointmentPopover / _aptPopoverEventId
    // are defined in index.blade.php and shared across tabs).

    // Auto-init on DOM ready
    $(function() {
        if ($('#drg-container').length) {
            new DoctorResourceGrid({
                container: '#drg-container',
                doctorsUrl: '/appointments/doctors',
                eventsUrl:  '/appointments/calendar-events'
            });
        }
    });

})(jQuery);
