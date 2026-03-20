<?php

return [
    'title' => 'Doctor Schedules',
    'schedule_form' => 'Schedule Form',
    'list_view' => 'List View',
    'calendar_view' => 'Calendar View',
    'grid_view' => 'Schedule Grid',
    'all_doctors' => 'All Doctors',

    // Fields
    'doctor' => 'Doctor',
    'date' => 'Date',
    'start_time' => 'Start Time',
    'end_time' => 'End Time',
    'time_range' => 'Time Range',
    'max_patients' => 'Max Patients',
    'branch' => 'Branch',
    'notes' => 'Notes',
    'recurring' => 'Recurring',
    'enable_recurring' => 'Enable recurring schedule',
    'recurring_pattern' => 'Recurring Pattern',
    'recurring_until' => 'Recurring Until',

    // Patterns
    'pattern_daily' => 'Daily',
    'pattern_weekly' => 'Weekly',
    'pattern_monthly' => 'Monthly',

    // Placeholders
    'select_doctor' => 'Select Doctor',
    'select_branch' => 'Select Branch',

    // Weekdays
    'mon' => 'Mon',
    'tue' => 'Tue',
    'wed' => 'Wed',
    'thu' => 'Thu',
    'fri' => 'Fri',
    'sat' => 'Sat',
    'sun' => 'Sun',

    // Grid
    'shift_buttons_label' => 'Shifts',
    'click_to_assign' => 'Click a shift button, then click a cell in the grid to assign',
    'no_schedules' => 'No schedules this month',
    'legacy_shift' => 'Legacy',
    'already_assigned' => 'This shift is already assigned',
    'not_found' => 'Schedule not found',

    // Conflict
    'time_conflict' => 'Time conflict with existing shift ":shift" (:time)',

    // Copy
    'copy_week' => 'Copy Schedule',
    'copy_month' => 'Copy Last Month',
    'source_week' => 'Use',
    'source_week_suffix' => "week's schedule",
    'copy_to' => 'Copy to',
    'copy_confirm' => 'Confirm',
    'source_week_empty' => 'Source week has no schedule data',
    'source_month_empty' => 'Previous month has no schedule data',
    'copy_week_success' => 'Successfully copied :count schedules',
    'copy_month_success' => 'Successfully copied :count schedules',
    'copy_failed' => 'Copy failed, please try again',

    // Permission
    'cannot_edit_others' => 'You can only edit your own schedule',
    'cannot_delete_past' => 'Cannot delete schedule for today or past dates',
    'has_linked_appointments' => 'This schedule has :count linked appointments. Please cancel or reschedule them first',

    // Validation
    'doctor_required' => 'Please select a doctor',
    'date_required' => 'Please enter schedule date',
    'start_time_required' => 'Please enter start time',
    'end_time_required' => 'Please enter end time',
    'end_time_after_start' => 'End time must be after start time',
    'max_patients_required' => 'Please enter maximum patients',

    // Messages
    'added_successfully' => 'Schedule added successfully',
    'updated_successfully' => 'Schedule updated successfully',
    'deleted_successfully' => 'Schedule deleted successfully',
    'delete_confirm' => 'Are you sure you want to delete this schedule?',

    // Export
    'export' => 'Export Schedules',

    // AG-073: Waiting queue guard
    'delete_has_waiting_patients' => 'Cannot delete: :count patient(s) are waiting or in treatment on this schedule date',

    // Shift settings
    'shift_settings' => 'Shift Settings',
];
