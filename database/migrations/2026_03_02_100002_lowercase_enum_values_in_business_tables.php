<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert enum columns to varchar and normalize existing values to lowercase.
     * Supports MySQL. For other drivers, raw statements may need adjustment.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        // appointments.status
        DB::statement("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'waiting'");
        DB::table('appointments')->update(['status' => DB::raw('LOWER(status)')]);

        // appointments.visit_information (nullable)
        DB::statement("ALTER TABLE appointments MODIFY COLUMN visit_information VARCHAR(50) NULL");
        DB::table('appointments')->whereNotNull('visit_information')->update(['visit_information' => DB::raw('LOWER(visit_information)')]);

        // medical_cases.status
        DB::statement("ALTER TABLE medical_cases MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'open'");
        DB::table('medical_cases')->update(['status' => DB::raw('LOWER(status)')]);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('Waiting', 'Treatment Complete', 'Treatment Incomplete', 'Rejected') NOT NULL DEFAULT 'Waiting'");
        DB::statement("ALTER TABLE appointments MODIFY COLUMN visit_information ENUM('Single Treatment', 'Review Treatment') NULL");
        DB::statement("ALTER TABLE medical_cases MODIFY COLUMN status ENUM('Open', 'Closed', 'Follow-up') NOT NULL DEFAULT 'Open'");
    }
};
