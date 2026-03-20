<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move from schedule group to clinic group
        DB::table('system_settings')
            ->where('key', 'schedule.require_schedule_for_booking')
            ->update([
                'key'   => 'clinic.require_schedule_for_booking',
                'group' => 'clinic',
            ]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'clinic.require_schedule_for_booking')
            ->update([
                'key'   => 'schedule.require_schedule_for_booking',
                'group' => 'schedule',
            ]);
    }
};
