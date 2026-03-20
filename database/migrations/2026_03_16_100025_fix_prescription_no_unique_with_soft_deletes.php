<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AG-072: Fix prescription_no unique constraint to work with soft deletes.
 *
 * Replace simple unique('prescription_no') with composite unique(prescription_no, deleted_at).
 * This allows soft-deleted prescriptions to have prescription_nos that don't block
 * new prescriptions with the same number.
 *
 * Same pattern as 2026_03_08_100001_fix_medical_templates_code_unique_with_soft_deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the simple unique index
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique(['prescription_no']);
        });

        // Add composite unique index: active prescriptions must have unique prescription_no
        // Soft-deleted rows (deleted_at IS NOT NULL) won't conflict with each other or with active rows
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unique(['prescription_no', 'deleted_at'], 'prescriptions_no_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique('prescriptions_no_deleted_at_unique');
            $table->unique('prescription_no');
        });
    }
};
