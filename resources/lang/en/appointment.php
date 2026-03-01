<?php

/**
 * Appointments Language Lines
 * The following language lines are used in appointments management module.
 * You are free to modify these language lines according to your application's requirements.
 * @package Resources\Lang\En
 */
return [
    'appointments' => 'Appointments',
    'appointment_mgt' => 'Appointments Mgt',
    'appointment_no' => 'Appointment No',
    'appointment_date' => 'Appointment Date',
    'appointment_time' => 'Appointment Time',
    'appointment_category' => 'Appointment Category',
    'appointment_status' => 'Appointment Status',
    'date' => 'Date',
    'status' => 'Status',
    'appointment_calender' => 'Appointment Calender',
    'appointments_calender' => 'Appointments Calender',
    'patient' => 'Patient',
    'doctor' => 'Doctor',
    'visit_information' => 'Visit Information',
    'invoice_status' => 'Invoice status',
    'filter_appointments' => 'Filter Appointments',
    'enter_appointment_no' => 'Enter appointment No',
    'choose_patient' => 'Choose patient',
    'choose_doctor' => 'Choose doctor',
    'procedure_done_by' => 'Procedure Done By.',
    'walk_in' => 'Walk In',
    'reactivate_appointment' => 'Reactivate Appointment',
    're_activate_appointment' => 'Re-Activate Appointment',
    'reschedule_appointment' => 'Reschedule Appointment',
    'delete_appointment_warning' => 'Your will not be able to recover this Appointment!',
    'export_appointments' => 'Export Appointments',
    'generate_invoice' => 'Generate Invoice',
    'select_procedure' => 'Select Procedure',
    'enter_tooth_number' => 'Enter Tooth Number',
    'enter_qty' => 'Enter Qty',
    'enter_unit_price' => 'Enter Unit Price',
    'add_appointment' => 'Add Appointment',
    'general_notes' => 'General Notes',
    'optional' => 'optional',
    'enter_general_notes' => 'Enter general notes here (if any)',
    'appointment_form' => 'Appointment Form',
    'appointment' => 'Appointment',
    'reschedule' => 'Reschedule',

    // Appointment Status
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'scheduled' => 'Scheduled',
    'arrived' => 'Arrived',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'no_show' => 'No Show',
    'rescheduled' => 'Rescheduled',
    'waiting' => 'Waiting',

    // Form section headers (design spec)
    'patient_info' => 'Patient & Doctor',
    'visit_info' => 'Visit Information',
    'schedule_info' => 'Schedule',

    // Search and filters
    'quick_search_placeholder' => 'Search patient, phone, appointment no...',
    'invoiced' => 'Invoiced',
    'appointment_status' => 'Appointment Status',

    // Time slots
    'morning' => 'Morning',
    'afternoon' => 'Afternoon',
    'time_slot' => 'Time Slot',
    'select_time_slot' => 'Select Time Slot',

    // Design spec F-APT-001: New appointment form
    'new_appointment' => 'New Appointment',
    'search_patient_placeholder' => 'Search by name or phone',
    'create_new_patient' => '+ New Patient',
    'selected_patient_info' => 'Selected Patient',
    'last_visit' => 'Last Visit',
    'allergy_label' => 'Allergies',
    'no_allergy' => 'None',
    'select_date' => 'Date',
    'select_doctor' => 'Doctor',
    'time_slot_selection' => 'Time Slot',
    'available' => 'Available',
    'selected' => 'Selected',
    'booked' => 'Booked',
    'rest_time' => 'Rest',
    'chair_selection' => 'Chair',
    'auto_assign' => 'Auto Assign',
    'appointment_service' => 'Service',
    'visit_type' => 'Visit Type',
    'first_visit' => 'First Visit',
    'revisit' => 'Follow-up',
    'estimated_duration_label' => 'Duration',
    'minutes' => 'minutes',
    'confirm_appointment' => 'Confirm',
    'patient_has_appointment_today' => 'Patient already has an appointment today. View it?',
    'patient_no_show_warning' => 'Patient has :count consecutive no-shows',
    'slot_occupied_by' => 'Booked by: ',
    'consecutive_slots_required' => 'Duration requires :count consecutive slots',
    'chair_optional_hint' => 'Optional, auto-assigned if empty',
    'weekday_mon' => 'Mon',
    'weekday_tue' => 'Tue',
    'weekday_wed' => 'Wed',
    'weekday_thu' => 'Thu',
    'weekday_fri' => 'Fri',
    'weekday_sat' => 'Sat',
    'weekday_sun' => 'Sun',
    'notes' => 'Notes',
    'no_available_slots' => 'No available time slots',

    'past_slot' => 'Past',
    'all_slots_past' => 'All time slots have passed for today. Please select another date.',

    // Validation messages
    'patient_required' => 'Please select a patient',
    'doctor_required' => 'Please select a doctor',
    'date_required' => 'Please select an appointment date',
    'time_required' => 'Please select an appointment time',

    // Popover (calendar event card)
    'popover_time' => 'Time',
    'popover_project' => 'Service',
    'popover_status' => 'Status',
    'popover_send_sms' => 'SMS',
    'doctor_day_view' => 'Doctor Day View',
    'no_appointments' => 'No appointments',
    'doctor_no_schedule_warning' => 'This doctor has no schedule for the selected date. Showing default time slots.',
    'no_phone_for_sms' => 'Patient has no phone number. Cannot send SMS.',

    // Resource grid schedule
    'no_schedule' => 'No schedule',
    'off_schedule_warning' => 'This time slot is outside the doctor\'s schedule',

    // Overbooking
    'overbooking_conflict' => 'This doctor already has an appointment during the selected time slot',

    // Advance booking limits
    'max_advance_days_exceeded' => 'Appointments can only be booked up to :days days in advance',
    'min_advance_hours_not_met' => 'Appointments must be booked at least :hours hours in advance',
];