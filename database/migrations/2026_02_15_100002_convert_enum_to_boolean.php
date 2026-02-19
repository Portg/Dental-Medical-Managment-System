<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convert ENUM('Yes','No') / ENUM('true','false') / ENUM('yes','no') columns to BOOLEAN.
 *
 * MySQL stores ENUM values by 1-based ordinal:
 *   first value  → 1  (maps to TRUE)
 *   second value → 2  (must be fixed to 0 / FALSE)
 *
 * Strategy: ALTER to TINYINT(1), then UPDATE ordinal-2 rows to 0.
 */
class ConvertEnumToBoolean extends Migration
{
    /**
     * Column definitions: [table, column, default (after conversion)]
     */
    private array $columns = [
        // ENUM('true','false') — unused but still converting for correctness
        ['branches',             'is_active',     1],
        ['self_accounts',        'is_active',     1],
        // ENUM('Yes','No')
        ['users',                'is_doctor',     0],
        ['patients',             'has_insurance',  0],
        ['holidays',             'repeat_date',   0],
        ['online_bookings',      'visit_history', 0],
        // ENUM('yes','no')
        ['accounting_equations', 'active_tab',    0],
    ];

    public function up()
    {
        foreach ($this->columns as [$table, $column, $default]) {
            // Step 1: Convert ENUM → TINYINT(1)
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` TINYINT(1) NOT NULL DEFAULT {$default}");

            // Step 2: Fix second-enum-value rows (ordinal 2 → 0)
            DB::statement("UPDATE `{$table}` SET `{$column}` = 0 WHERE `{$column}` = 2");
        }
    }

    public function down()
    {
        // Reverse: convert BOOLEAN back to original ENUM types
        $enumDefs = [
            ['branches',             'is_active',     "ENUM('true','false')",  "'true'"],
            ['self_accounts',        'is_active',     "ENUM('true','false')",  "'true'"],
            ['users',                'is_doctor',     "ENUM('Yes','No')",      "'No'"],
            ['patients',             'has_insurance',  "ENUM('Yes','No')",      "'No'"],
            ['holidays',             'repeat_date',   "ENUM('Yes','No')",      "'No'"],
            ['online_bookings',      'visit_history', "ENUM('Yes','No')",      "'No'"],
            ['accounting_equations', 'active_tab',    "ENUM('yes','no')",      "'no'"],
        ];

        foreach ($enumDefs as [$table, $column, $enumType, $default]) {
            // Step 1: Map boolean back to ordinal values (0→2 for second enum value)
            DB::statement("UPDATE `{$table}` SET `{$column}` = 2 WHERE `{$column}` = 0");

            // Step 2: Convert TINYINT(1) → ENUM
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$enumType} NOT NULL DEFAULT {$default}");
        }
    }
}
