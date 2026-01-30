<?php

return [
    // Page title
    'title' => 'Waiting Queue',
    'display_screen_title' => 'Queue Display',

    // Actions
    'patient_check_in' => 'Patient Check-In',
    'open_display_screen' => 'Open Display Screen',
    'confirm_check_in' => 'Confirm Check-In',
    'select_appointment' => 'Select Today\'s Appointment',
    'call_patient' => 'Call Patient',
    'select_chair' => 'Select Chair',
    'no_chair' => 'No Chair',
    'confirm_call' => 'Confirm Call',
    'call' => 'Call',
    'recall' => 'Recall',
    'start_treatment' => 'Start Treatment',
    'complete' => 'Complete',
    'confirm_cancel' => 'Are you sure you want to cancel this queue?',

    // Status
    'status' => [
        'waiting' => 'Waiting',
        'called' => 'Called',
        'in_treatment' => 'In Treatment',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No Show',
    ],

    // Fields
    'queue_number' => 'Queue No.',
    'check_in_time' => 'Check-In Time',
    'waited_time' => 'Waited',
    'minutes' => 'min',
    'estimated_wait' => 'Est. Wait',

    // Display screen
    'current_calling' => 'Now Calling',
    'please_wait' => 'Please Wait',
    'no_current_calling' => 'No Current Call',
    'waiting_list' => 'Waiting List',
    'no_waiting' => 'No Patients Waiting',
    'in_treatment_now' => 'Currently In Treatment',
    'display_tip' => 'Please proceed to the designated room when called',

    // Tips for display screen
    'tips' => [
        '1' => 'Please keep your phone accessible and proceed when called',
        '2' => 'Please notify the reception if you need to leave during waiting',
        '3' => 'Please cooperate with staff for treatment preparation',
        '4' => 'For any questions, please ask the reception staff',
    ],

    // Messages
    'check_in_success' => 'Check-in successful',
    'call_success' => 'Patient called successfully',
    'treatment_started' => 'Treatment started',
    'treatment_completed' => 'Treatment completed',
    'cancelled' => 'Queue cancelled',
    'invalid_status_for_call' => 'Cannot call patient with current status',
    'invalid_status_for_start' => 'Cannot start treatment with current status',
    'invalid_status_for_complete' => 'Cannot complete treatment with current status',
    'cannot_cancel' => 'Cannot cancel with current status',
    'no_appointments_today' => 'No appointments available for check-in today',
    'no_waiting_patients' => 'No patients waiting',

    // Doctor queue
    'my_queue' => 'My Queue',
    'call_next' => 'Call Next',
    'current_patient' => 'Current Patient',
    'waiting_patients' => 'Waiting Patients',
];
