<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NIN Encryption — Blind Index Key
    |--------------------------------------------------------------------------
    |
    | HMAC-SHA256 key used to compute blind indexes for encrypted NIN fields.
    | Generate with: php -r "echo bin2hex(random_bytes(32));"
    |
    */
    'nin_blind_index_key' => env('NIN_BLIND_INDEX_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Display Masking
    |--------------------------------------------------------------------------
    |
    | When enabled, sensitive fields are masked on list/detail pages for users
    | WITHOUT the 'view-sensitive-data' permission — currently nurses and
    | receptionists (see DefaultRolePermissionsSeeder). Those roles cannot
    | unmask at all; there is no reveal button for them.
    |
    | Users WITH that permission (admin, doctor) see the values unmasked on
    | page load, and the toggle reads "Hide" instead of "Reveal".
    | See DataMaskingService::shouldDisplayUnmasked().
    |
    | Audit trail: /patients/{id}/reveal-pii logs 'Patient:reveal_pii', but
    | users who see PII by default never click that button. PatientController@show
    | therefore logs 'Patient:pii_shown' whenever the page renders unmasked, so
    | "who actually saw the phone / ID number" stays answerable either way.
    | Masked viewers only produce 'Patient:view_detail'.
    |
    | Setting DISPLAY_MASKING_ENABLED=false disables masking for everyone,
    | including nurses and receptionists.
    |
    */
    'display_masking' => [
        'enabled' => env('DISPLAY_MASKING_ENABLED', true),
        'fields' => [
            'surname', 'othername', 'full_name',
            'phone_no', 'alternative_no', 'nin', 'email', 'address',
            'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Masking
    |--------------------------------------------------------------------------
    |
    | When enabled, PII fields in exported Excel/CSV files are masked.
    |
    */
    'export_masking_enabled' => env('EXPORT_MASKING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Export Frequency Alert
    |--------------------------------------------------------------------------
    |
    | If a single user triggers more than `threshold` exports within `window`
    | minutes, a warning is logged.
    |
    */
    'export_alert' => [
        'threshold' => 5,
        'window_minutes' => 60,
    ],

];
