/**
 * Doctor Schedule Monthly Grid
 * Handles the monthly grid view for doctor scheduling.
 */
var ScheduleGrid = (function () {
    var currentMonth = '';       // 'YYYY-MM'
    var selectedShiftId = null;
    var gridData = {};           // { 'doctorId_day': [{id, shift_id, name, color}] }
    var shifts = [];             // from server
    var doctors = [];            // from server
    var CSRF_TOKEN = '';

    function init(config) {
        currentMonth = config.currentMonth;
        shifts = config.shifts;
        doctors = config.doctors;
        CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

        bindEvents();
        loadGridData();
    }

    function bindEvents() {
        // Month navigation
        $('#btn-prev-month').on('click', function () {
            changeMonth(-1);
        });
        $('#btn-next-month').on('click', function () {
            changeMonth(1);
        });

        // Shift button selection
        $(document).on('click', '.shift-btn', function () {
            var shiftId = $(this).data('shift-id');
            if (selectedShiftId === shiftId) {
                selectedShiftId = null;
                $('.shift-btn').removeClass('selected');
            } else {
                selectedShiftId = shiftId;
                $('.shift-btn').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        // Cell click → assign selected shift
        $(document).on('click', '.day-cell', function () {
            if (!selectedShiftId) return;
            var doctorId = $(this).data('doctor-id');
            var day = $(this).data('day');
            assignShift(doctorId, day);
        });

        // Right-click on shift badge → show context menu
        $(document).on('contextmenu', '.cell-shift', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showContextMenu(e.pageX, e.pageY, $(this).data('schedule-id'));
        });

        // Click on shift badge → remove (alternative to right-click)
        $(document).on('click', '.cell-shift', function (e) {
            e.stopPropagation();
            var scheduleId = $(this).data('schedule-id');
            removeSchedule(scheduleId);
        });

        // Hide context menu
        $(document).on('click', function () {
            hideContextMenu();
        });

        // Drag and drop
        $(document).on('dragstart', '.shift-btn', function (e) {
            e.originalEvent.dataTransfer.setData('text/plain', $(this).data('shift-id'));
            selectedShiftId = $(this).data('shift-id');
            $('.shift-btn').removeClass('selected');
            $(this).addClass('selected');
        });

        $(document).on('dragover', '.day-cell', function (e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', '.day-cell', function () {
            $(this).removeClass('drag-over');
        });

        $(document).on('drop', '.day-cell', function (e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            var shiftId = parseInt(e.originalEvent.dataTransfer.getData('text/plain'));
            var doctorId = $(this).data('doctor-id');
            var day = $(this).data('day');
            selectedShiftId = shiftId;
            assignShift(doctorId, day);
        });

        // Copy week
        $('#btn-copy-week').on('click', function () {
            copyWeek();
        });

        // Copy previous month
        $('#btn-copy-month').on('click', function () {
            copyMonth();
        });

        // Shift settings modal
        $('#btn-shift-settings').on('click', function () {
            openShiftSettings();
        });
    }

    function changeMonth(delta) {
        var parts = currentMonth.split('-');
        var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1 + delta, 1);
        currentMonth = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        renderGridHeader();
        loadGridData();
    }

    function loadGridData() {
        $.ajax({
            url: '/doctor-schedules/grid-data',
            type: 'GET',
            data: { month: currentMonth },
            success: function (resp) {
                if (resp.status === 1) {
                    gridData = resp.data;
                    renderGridBody();
                }
            }
        });
    }

    function renderGridHeader() {
        var parts = currentMonth.split('-');
        var year = parseInt(parts[0]);
        var month = parseInt(parts[1]);
        var daysInMonth = new Date(year, month, 0).getDate();
        var today = new Date();
        var todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        var weekdays = [
            LanguageManager.trans('doctor_schedules.sun'),
            LanguageManager.trans('doctor_schedules.mon'),
            LanguageManager.trans('doctor_schedules.tue'),
            LanguageManager.trans('doctor_schedules.wed'),
            LanguageManager.trans('doctor_schedules.thu'),
            LanguageManager.trans('doctor_schedules.fri'),
            LanguageManager.trans('doctor_schedules.sat')
        ];

        // Update month label
        $('#month-label').text(year + '-' + String(month).padStart(2, '0'));

        // Build header rows
        var dayRow = '<th class="doctor-col"></th>';
        var weekdayRow = '<th class="doctor-col"></th>';

        for (var d = 1; d <= daysInMonth; d++) {
            var dateObj = new Date(year, month - 1, d);
            var dow = dateObj.getDay(); // 0=Sun
            var dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            var isWeekend = (dow === 0 || dow === 6);
            var isToday = (dateStr === todayStr);
            var classes = [];
            if (isWeekend) classes.push('weekend');
            if (isToday) classes.push('today');

            dayRow += '<th class="' + classes.join(' ') + '">' + d + '</th>';
            weekdayRow += '<th class="' + classes.join(' ') + '">' + weekdays[dow] + '</th>';
        }

        $('#grid-day-row').html(dayRow);
        $('#grid-weekday-row').html(weekdayRow);
    }

    function renderGridBody() {
        var parts = currentMonth.split('-');
        var year = parseInt(parts[0]);
        var month = parseInt(parts[1]);
        var daysInMonth = new Date(year, month, 0).getDate();

        var html = '';
        for (var i = 0; i < doctors.length; i++) {
            var doc = doctors[i];
            var initial = (doc.surname || '').substring(0, 1);
            html += '<tr>';
            html += '<td class="doctor-col">';
            html += '<span class="doctor-avatar">' + initial + '</span>';
            html += '<span>' + doc.full_name + '</span>';
            html += '</td>';

            for (var d = 1; d <= daysInMonth; d++) {
                var key = doc.id + '_' + d;
                var cellShifts = gridData[key] || [];
                var cellHtml = '';

                for (var s = 0; s < cellShifts.length; s++) {
                    var cs = cellShifts[s];
                    cellHtml += '<span class="cell-shift" style="background-color:' + cs.color + '" ' +
                        'data-schedule-id="' + cs.id + '" title="' + cs.name + '">' +
                        cs.name + '</span>';
                }

                html += '<td class="day-cell" data-doctor-id="' + doc.id + '" data-day="' + d + '">' +
                    cellHtml + '</td>';
            }

            html += '</tr>';
        }

        $('#grid-body').html(html);
    }

    function assignShift(doctorId, day) {
        var date = currentMonth + '-' + String(day).padStart(2, '0');

        $.ajax({
            url: '/doctor-schedules/assign',
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                doctor_id: doctorId,
                date: date,
                shift_id: selectedShiftId
            },
            success: function (resp) {
                if (resp.status === 1 && resp.data) {
                    // Add to local grid data
                    var key = doctorId + '_' + day;
                    if (!gridData[key]) gridData[key] = [];
                    gridData[key].push(resp.data);

                    // Re-render the cell
                    var cell = $('.day-cell[data-doctor-id="' + doctorId + '"][data-day="' + day + '"]');
                    appendShiftToCell(cell, resp.data);
                } else {
                    toastr.warning(resp.message);
                }
            },
            error: function () {
                toastr.error(LanguageManager.trans('common.error'));
            }
        });
    }

    function appendShiftToCell(cell, shiftData) {
        var badge = '<span class="cell-shift" style="background-color:' + shiftData.color + '" ' +
            'data-schedule-id="' + shiftData.id + '" title="' + shiftData.name + '">' +
            shiftData.name + '</span>';
        cell.append(badge);
    }

    function removeSchedule(scheduleId) {
        swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('doctor_schedules.delete_confirm'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-danger',
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: '/doctor-schedules/remove',
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    schedule_id: scheduleId
                },
                success: function (resp) {
                    swal.close();
                    if (resp.status === 1) {
                        // Remove badge from DOM
                        $('[data-schedule-id="' + scheduleId + '"]').remove();

                        // Remove from local data
                        for (var key in gridData) {
                            gridData[key] = gridData[key].filter(function (s) {
                                return s.id !== scheduleId;
                            });
                        }

                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function () {
                    swal.close();
                    toastr.error(LanguageManager.trans('common.error'));
                }
            });
        });
    }

    function showContextMenu(x, y, scheduleId) {
        var menu = $('#schedule-context-menu');
        menu.css({ left: x, top: y }).show();
        menu.data('schedule-id', scheduleId);
    }

    function hideContextMenu() {
        $('#schedule-context-menu').hide();
    }

    function copyWeek() {
        var sourceDate = $('#copy-source-date').val();
        var targetDate = $('#copy-target-date').val();

        if (!sourceDate || !targetDate) {
            toastr.warning(LanguageManager.trans('doctor_schedules.date_required'));
            return;
        }

        $.ajax({
            url: '/doctor-schedules/copy-week',
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                source_date: sourceDate,
                target_date: targetDate
            },
            success: function (resp) {
                if (resp.status === 1) {
                    toastr.success(resp.message);
                    loadGridData();
                } else {
                    toastr.warning(resp.message);
                }
            }
        });
    }

    function copyMonth() {
        swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('doctor_schedules.copy_month'),
            type: 'info',
            showCancelButton: true,
            confirmButtonText: LanguageManager.trans('doctor_schedules.copy_confirm'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: '/doctor-schedules/copy-month',
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    target_month: currentMonth
                },
                success: function (resp) {
                    swal.close();
                    if (resp.status === 1) {
                        toastr.success(resp.message);
                        loadGridData();
                    } else {
                        toastr.warning(resp.message);
                    }
                }
            });
        });
    }

    // ===== Shift Settings Modal =====

    function openShiftSettings() {
        loadShiftList();
        $('#shift-settings-modal').modal('show');
    }

    function loadShiftList() {
        $.ajax({
            url: '/shifts',
            type: 'GET',
            success: function (resp) {
                if (resp.status === 1) {
                    renderShiftList(resp.data);
                }
            }
        });
    }

    function renderShiftList(shiftList) {
        var html = '';
        for (var i = 0; i < shiftList.length; i++) {
            var s = shiftList[i];
            var statusLabel = s.work_status === 'on_duty'
                ? LanguageManager.trans('shifts.status_on_duty')
                : LanguageManager.trans('shifts.status_rest');

            html += '<tr data-shift-id="' + s.id + '">';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + s.name + '</td>';
            html += '<td>' + (s.start_time || '').substring(0, 5) + '</td>';
            html += '<td>' + (s.end_time || '').substring(0, 5) + '</td>';
            html += '<td>' + statusLabel + '</td>';
            html += '<td><span class="color-preview" style="background-color:' + s.color + '"></span></td>';
            html += '<td>';
            html += '<div class="btn-group">';
            html += '<button class="btn btn-xs btn-default" onclick="ScheduleGrid.editShift(' + s.id + ')"><i class="fa fa-edit"></i></button> ';
            html += '<button class="btn btn-xs btn-danger" onclick="ScheduleGrid.deleteShift(' + s.id + ')"><i class="fa fa-trash"></i></button> ';
            html += '<button class="btn btn-xs btn-default" onclick="ScheduleGrid.moveShift(' + s.id + ', \'up\')"><i class="fa fa-arrow-up"></i></button> ';
            html += '<button class="btn btn-xs btn-default" onclick="ScheduleGrid.moveShift(' + s.id + ', \'down\')"><i class="fa fa-arrow-down"></i></button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        }

        $('#shift-list-body').html(html);
    }

    function saveShift() {
        var shiftId = $('#shift-edit-id').val();
        var data = {
            _token: CSRF_TOKEN,
            name: $('#shift-name').val(),
            start_time: $('#shift-start-time').val(),
            end_time: $('#shift-end-time').val(),
            work_status: $('#shift-work-status').val(),
            color: $('#shift-color').val(),
            max_patients: $('#shift-max-patients').val()
        };

        var url = shiftId ? '/shifts/' + shiftId : '/shifts';
        var method = shiftId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function (resp) {
                if (resp.status === 1) {
                    toastr.success(resp.message);
                    $('#shift-edit-form')[0].reset();
                    $('#shift-edit-id').val('');
                    loadShiftList();
                    // Reload page to update shift buttons
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    toastr.error(resp.message);
                }
            }
        });
    }

    function editShift(id) {
        $.ajax({
            url: '/shifts',
            type: 'GET',
            success: function (resp) {
                if (resp.status === 1) {
                    var shift = resp.data.find(function (s) { return s.id === id; });
                    if (shift) {
                        $('#shift-edit-id').val(shift.id);
                        $('#shift-name').val(shift.name);
                        $('#shift-start-time').val((shift.start_time || '').substring(0, 5));
                        $('#shift-end-time').val((shift.end_time || '').substring(0, 5));
                        $('#shift-work-status').val(shift.work_status);
                        $('#shift-color').val(shift.color);
                        $('#shift-max-patients').val(shift.max_patients);
                    }
                }
            }
        });
    }

    function deleteShift(id) {
        swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('shifts.delete_confirm'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-danger',
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: '/shifts/' + id,
                type: 'DELETE',
                data: { _token: CSRF_TOKEN },
                success: function (resp) {
                    swal.close();
                    if (resp.status === 1) {
                        toastr.success(resp.message);
                        loadShiftList();
                        setTimeout(function () { location.reload(); }, 800);
                    } else {
                        toastr.error(resp.message);
                    }
                }
            });
        });
    }

    function moveShift(id, direction) {
        var rows = $('#shift-list-body tr');
        var ids = [];
        rows.each(function () {
            ids.push(parseInt($(this).data('shift-id')));
        });

        var idx = ids.indexOf(id);
        if (idx < 0) return;

        if (direction === 'up' && idx > 0) {
            ids.splice(idx, 1);
            ids.splice(idx - 1, 0, id);
        } else if (direction === 'down' && idx < ids.length - 1) {
            ids.splice(idx, 1);
            ids.splice(idx + 1, 0, id);
        } else {
            return;
        }

        $.ajax({
            url: '/shifts/reorder',
            type: 'POST',
            data: { _token: CSRF_TOKEN, ids: ids },
            success: function (resp) {
                if (resp.status === 1) {
                    loadShiftList();
                }
            }
        });
    }

    // Public API
    return {
        init: init,
        loadGridData: loadGridData,
        renderGridHeader: renderGridHeader,
        saveShift: saveShift,
        editShift: editShift,
        deleteShift: deleteShift,
        moveShift: moveShift
    };
})();
