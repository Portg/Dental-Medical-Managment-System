/**
 * 资源泳道网格 —— 一天的时间轴纵向排，资源横向分列。
 *
 * 两种模式共用这一份代码：
 *   doctor —— 列 = 医生，带排班时段底色（非排班时段拦下拖选）
 *   chair  —— 列 = 诊室/椅位，没有排班概念，全天可拖选
 *
 * 之所以泛化而不是复制一份「诊室网格」：拖选建预约、事件块定位、当前时间线、
 * 气泡复用这些逻辑有三百来行，复制出去之后改一处就得记得改两处。两种模式的
 * 差别只有三件事 —— 列从哪来、事件按哪个字段分列、拖选带哪个 id 给预约抽屉。
 *
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

    function ResourceGrid(options) {
        this.container     = $(options.container);
        this.mode          = options.mode || 'doctor';   // 'doctor' | 'chair'
        this.resourcesUrl  = options.resourcesUrl  || '/appointments/doctors';
        this.eventsUrl     = options.eventsUrl     || '/appointments/calendar-events';
        // 工具栏按钮的 id 前缀：两个网格同时在页面上，选择器不能撞
        this.prefix        = options.prefix || 'drg';
        this.currentDate   = new Date();
        this.currentDate.setHours(0,0,0,0);
        this.resources     = [];
        this.events        = [];
        this._rendered     = false;

        this._bindToolbar();
        this._bindGridEvents();
    }

    /** 事件归到哪一列：医生模式看 doctor_id，诊室模式看 chair_id */
    ResourceGrid.prototype._resourceIdOf = function(evt) {
        var ep = evt.extendedProps || {};
        if (this.mode === 'chair') {
            // 没排椅位的归进「未分配诊室」那一列（id 0），不能让它们消失
            return ep.chair_id ? String(ep.chair_id) : '0';
        }
        return String(evt.resourceId || ep.doctor_id || '');
    };

    ResourceGrid.prototype._id = function(suffix) {
        return '#' + this.prefix + '-' + suffix;
    };

    ResourceGrid.prototype._bindToolbar = function() {
        var self = this;
        $(this._id('prev')).on('click', function()  { self._shiftDate(-1); });
        $(this._id('next')).on('click', function()  { self._shiftDate(1); });
        $(this._id('today')).on('click', function() {
            self.currentDate = new Date();
            self.currentDate.setHours(0,0,0,0);
            self._load();
        });
    };

    ResourceGrid.prototype._shiftDate = function(days) {
        this.currentDate.setDate(this.currentDate.getDate() + days);
        this._load();
    };

    ResourceGrid.prototype._formatDate = function(d) {
        var y = d.getFullYear();
        var m = ('0' + (d.getMonth()+1)).slice(-2);
        var dd = ('0' + d.getDate()).slice(-2);
        return y + '-' + m + '-' + dd;
    };

    ResourceGrid.prototype._formatDisplay = function(d) {
        var weekdayKeys = [
            'appointment.weekday_sun', 'appointment.weekday_mon',
            'appointment.weekday_tue', 'appointment.weekday_wed',
            'appointment.weekday_thu', 'appointment.weekday_fri',
            'appointment.weekday_sat'
        ];
        var weekday = LanguageManager.trans(weekdayKeys[d.getDay()]);
        return this._formatDate(d) + '  ' + weekday;
    };

    ResourceGrid.prototype.render = function() {
        if (!this._rendered) {
            this._rendered = true;
            this._load();
        }
    };

    ResourceGrid.prototype._load = function() {
        var self = this;
        var dateStr = this._formatDate(this.currentDate);
        $(this._id('date-label')).text(this._formatDisplay(this.currentDate));

        var nextDay = new Date(this.currentDate);
        nextDay.setDate(nextDay.getDate() + 1);
        var endStr = this._formatDate(nextDay);

        $.when(
            $.getJSON(this.resourcesUrl, { date: dateStr }),
            $.getJSON(this.eventsUrl, { start: dateStr, end: endStr })
        ).done(function(resRes, evtRes) {
            self.resources = resRes[0] || resRes;
            self.events    = evtRes[0] || evtRes;
            self._buildGrid();
        }).fail(function() {
            self.container.html('<div class="drg-empty">' +
                LanguageManager.trans('common.error') + '</div>');
        });
    };

    ResourceGrid.prototype._timeToSlot = function(timeStr) {
        var parts = timeStr.split(':');
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        return Math.round(((h - START_HOUR) * 60 + m) / SLOT_MINUTES);
    };

    /**
     * Round a time string (HH:MM) to the nearest 30-min slot boundary
     * so it matches the appointment drawer's time-slot grid.
     */
    ResourceGrid.prototype._roundTo30 = function(timeStr) {
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
    ResourceGrid.prototype._isInSchedule = function(doctor, timeHHMM) {
        if (!doctor.schedule) return null;
        return timeHHMM >= doctor.schedule.start_time && timeHHMM < doctor.schedule.end_time;
    };

    ResourceGrid.prototype._buildGrid = function() {
        var self = this;

        if (!this.resources.length) {
            this.container.html('<div class="drg-empty">' +
                LanguageManager.trans('appointment.no_appointments') + '</div>');
            return;
        }

        // 事件按列归组。键一律转成字符串：资源 id 来自 JSON（数字），
        // 而 data-* 读回来是字符串，混用会让整列事件一个都对不上
        var eventsByRes = {};
        var countByRes  = {};
        this.resources.forEach(function(r) {
            eventsByRes[String(r.id)] = [];
            countByRes[String(r.id)]  = 0;
        });
        // 状态筛选与日历共用一份（AppointmentStatusFilter），切页签时不丢
        var visible = window.AppointmentStatusFilter
            ? window.AppointmentStatusFilter.filterEvents(this.events)
            : this.events;

        visible.forEach(function(evt) {
            var rid = self._resourceIdOf(evt);
            if (rid && eventsByRes[rid]) {
                eventsByRes[rid].push(evt);
                countByRes[rid]++;
            }
        });

        // Build HTML
        var html = '<table class="drg-table"><thead><tr>';
        html += '<th class="drg-time-col"></th>';
        this.resources.forEach(function(r) {
            // 排班时段只有医生有；诊室没有这个概念，表头就不占那一行
            var scheduleLabel = '';
            if (self.mode === 'doctor') {
                if (r.schedule) {
                    scheduleLabel = '<span class="drg-schedule-range">' +
                        r.schedule.start_time + '-' + r.schedule.end_time + '</span>';
                } else {
                    scheduleLabel = '<span class="drg-no-schedule">' +
                        LanguageManager.trans('appointment.no_schedule') + '</span>';
                }
            }
            html += '<th>' + self._esc(r.title) +
                '<span class="drg-doctor-count">(' + (countByRes[String(r.id)] || 0) + ')</span>' +
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
            this.resources.forEach(function(r) {
                var cellClass = 'drg-cell';
                // 诊室没有排班，全天可拖选；医生按排班上底色并拦下非排班时段
                if (self.mode === 'doctor') {
                    var inSchedule = self._isInSchedule(r, timeHHMM);
                    if (inSchedule === false) {
                        cellClass += ' drg-off-schedule';
                    } else if (inSchedule === null) {
                        cellClass += ' drg-no-schedule-cell';
                    }
                }
                html += '<td class="' + cellClass + '" data-slot="' + s +
                    '" data-res="' + r.id + '" data-time="' + timeHHMM + '"></td>';
            });
            html += '</tr>';
        }
        html += '</tbody></table>';

        this.container.html(html);

        // Place event blocks
        this.resources.forEach(function(r, colIdx) {
            var col = colIdx + 1;
            eventsByRes[String(r.id)].forEach(function(evt) {
                self._placeEvent(evt, col);
            });
        });

        // Now indicator
        this._placeNowLine();
    };

    /**
     * Bind event delegation once (not per _buildGrid call).
     */
    ResourceGrid.prototype._bindGridEvents = function() {
        var self = this;
        this._dragState = null;

        // --- Drag-select on cells: mousedown → mousemove → mouseup ---
        this.container.on('mousedown', '.drg-cell', function(e) {
            // Ignore clicks on existing events or off-schedule cells
            if ($(e.target).closest('.drg-event').length) return;
            var $cell = $(this);
            if ($cell.hasClass('drg-off-schedule')) {
                toastr.warning(LanguageManager.trans('appointment.off_schedule_warning'));
                return;
            }

            e.preventDefault(); // prevent text selection
            self._dragState = {
                resId:       String($cell.data('res')),
                startSlot:   parseInt($cell.data('slot'), 10),
                currentSlot: parseInt($cell.data('slot'), 10)
            };
            self._highlightRange(self._dragState.resId, self._dragState.startSlot, self._dragState.startSlot);
        });

        $(document).on('mousemove.drg', function(e) {
            if (!self._dragState) return;
            var $target = $(e.target).closest('.drg-cell');
            if (!$target.length) return;
            // 不能跨列拖：一次拖选只产生一个资源上的一段时间
            if (String($target.data('res')) !== self._dragState.resId) return;
            var slot = parseInt($target.data('slot'), 10);
            if (slot !== self._dragState.currentSlot) {
                self._dragState.currentSlot = slot;
                self._highlightRange(self._dragState.resId, self._dragState.startSlot, slot);
            }
        });

        $(document).on('mouseup.drg', function() {
            if (!self._dragState) return;
            var ds = self._dragState;
            self._dragState = null;
            self._clearHighlight();

            var minSlot = Math.min(ds.startSlot, ds.currentSlot);
            var maxSlot = Math.max(ds.startSlot, ds.currentSlot);
            var startTime = self._slotToTime(minSlot);
            var duration  = (maxSlot - minSlot + 1) * SLOT_MINUTES;
            var time      = self._roundTo30(startTime);

            if (typeof openAppointmentDrawer === 'function') {
                var prefill = {
                    date:     self._formatDate(self.currentDate),
                    time:     time,
                    duration: duration
                };
                // 诊室模式带 chair_id；「未分配诊室」那一列（id 0）不预填，
                // 否则会把 0 当成一个真实椅位写进去
                if (self.mode === 'chair') {
                    if (ds.resId !== '0') { prefill.chair_id = ds.resId; }
                } else {
                    prefill.doctor_id = ds.resId;
                }
                openAppointmentDrawer(prefill);
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

    /**
     * Highlight cells in a doctor column between slotA and slotB (inclusive).
     */
    ResourceGrid.prototype._highlightRange = function(resId, slotA, slotB) {
        this._clearHighlight();
        var minSlot = Math.min(slotA, slotB);
        var maxSlot = Math.max(slotA, slotB);
        for (var s = minSlot; s <= maxSlot; s++) {
            this.container.find('td.drg-cell[data-res="' + resId + '"][data-slot="' + s + '"]')
                .addClass('drg-drag-highlight');
        }
    };

    /**
     * Remove all drag highlight from cells.
     */
    ResourceGrid.prototype._clearHighlight = function() {
        this.container.find('.drg-drag-highlight').removeClass('drg-drag-highlight');
    };

    /**
     * Convert a slot index back to HH:MM time string.
     */
    ResourceGrid.prototype._slotToTime = function(slot) {
        var totalMinutes = START_HOUR * 60 + slot * SLOT_MINUTES;
        var h = Math.floor(totalMinutes / 60);
        var m = totalMinutes % 60;
        return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
    };

    ResourceGrid.prototype._placeEvent = function(evt, colIdx) {
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

    ResourceGrid.prototype._placeNowLine = function() {
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

    ResourceGrid.prototype._esc = function(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    // Popover reuse: resource grid events use the same global popover
    // as the FullCalendar view (showAppointmentPopover / _aptPopoverEventId
    // are defined in index.blade.php and shared across tabs).

    /** 筛选变了只重绘，不重新请求 —— 事件已经在手上 */
    ResourceGrid.prototype.redraw = function() {
        if (this._rendered && this.resources.length) { this._buildGrid(); }
    };

    // 对外暴露：两个网格由页面自己按需 render（页签切到才加载）
    window.AppointmentResourceGrid = ResourceGrid;

    // Auto-init on DOM ready
    $(function() {
        if ($('#drg-container').length) {
            window._drgInstance = new ResourceGrid({
                container:    '#drg-container',
                mode:         'doctor',
                prefix:       'drg',
                resourcesUrl: '/appointments/doctors',
                eventsUrl:    '/appointments/calendar-events'
            });
        }
        if ($('#crg-container').length) {
            window._crgInstance = new ResourceGrid({
                container:    '#crg-container',
                mode:         'chair',
                prefix:       'crg',
                resourcesUrl: '/appointments/chair-resources',
                eventsUrl:    '/appointments/calendar-events'
            });
        }
    });

})(jQuery);
