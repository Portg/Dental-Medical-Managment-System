<?php

return [
    'page_title' => 'System Settings',

    // Tabs
    'tab_clinic' => 'Clinic Settings',
    'tab_member' => 'Member Settings',

    // Messages
    'saved' => 'Settings saved successfully',
    'invalid_group' => 'Invalid settings group',

    // ── Clinic settings ─────────────────────────────────────────
    'clinic_time_range' => 'Appointment Time Range',
    'clinic_start_time' => 'Start Time',
    'clinic_end_time' => 'End Time',
    'clinic_slot_interval' => 'Slot Interval',
    'clinic_slot_interval_hint' => 'Appointment slot interval (minutes)',
    'clinic_default_duration' => 'Default Duration',
    'clinic_default_duration_hint' => 'Default appointment duration (minutes)',
    'clinic_grid_start_hour' => 'Grid Start Hour',
    'clinic_grid_end_hour' => 'Grid End Hour',
    'clinic_grid_range_hint' => 'Time range for doctor day-view grid',
    'clinic_display_settings' => 'Appointment Display',
    'clinic_hide_off_duty_doctors' => 'Hide Off-duty Doctors',
    'clinic_hide_off_duty_doctors_hint' => 'Hide doctors without schedule in appointment center',
    'clinic_show_appointment_notes' => 'Show Appointment Notes',
    'clinic_show_appointment_notes_hint' => 'Show appointment notes in calendar and list views',
    'clinic_rules' => 'Appointment Rules',
    'clinic_allow_overbooking' => 'Allow Overbooking',
    'clinic_allow_overbooking_hint' => 'Allow multiple appointments in the same time slot',
    'clinic_max_advance_days' => 'Max Advance Days',
    'clinic_max_advance_days_hint' => 'Maximum days in advance a patient can book (0 = unlimited)',
    'clinic_min_advance_hours' => 'Min Advance Hours',
    'clinic_min_advance_hours_hint' => 'Minimum hours in advance required for booking (0 = unlimited)',
    'minutes' => 'minutes',
    'hours' => 'hours',
    'days' => 'days',

    // ── Member settings ─────────────────────────────────────────
    'member_points_enabled' => 'Points System',
    'member_points_enabled_hint' => 'Enable or disable the points system globally',
    'member_points_expiry_days' => 'Points Expiry Days',
    'member_points_expiry_days_hint' => 'Days until points expire (0 = never)',
    'member_card_number_mode' => 'Card Number Mode',
    'member_card_mode_auto' => 'Auto Generate',
    'member_card_mode_phone' => 'Use Phone Number',
    'member_card_mode_manual' => 'Manual Input',
    'member_referral_bonus_enabled' => 'Referral Bonus',
    'member_referral_bonus_enabled_hint' => 'Award points when existing members refer new members',
    'member_points_exchange_rate' => 'Points Exchange Rate',
    'member_points_exchange_rate_hint' => 'X points = 1 currency unit',
    'member_points_exchange_enabled' => 'Points Exchange',
    'member_points_exchange_enabled_hint' => 'Allow members to exchange points for balance',
];
