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
    | When enabled, sensitive fields are masked by default on list/detail pages.
    | Users with 'view-sensitive-data' permission can reveal via the UI toggle.
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
