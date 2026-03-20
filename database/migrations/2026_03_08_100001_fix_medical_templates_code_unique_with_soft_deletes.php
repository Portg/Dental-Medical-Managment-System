<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix: Replace simple unique index on `code` with a conditional unique index
 * that excludes soft-deleted rows. This prevents conflicts when soft-deleted
 * templates have codes that are reused by new templates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_templates', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // MySQL 5.7+ supports unique index on (code, deleted_at) via nullable column
        // When deleted_at is NULL, code must be unique; soft-deleted rows don't conflict
        Schema::table('medical_templates', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
            $table->unique(['code', 'deleted_at'], 'medical_templates_code_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('medical_templates', function (Blueprint $table) {
            $table->dropUnique('medical_templates_code_deleted_at_unique');
            $table->string('code')->nullable(false)->change();
            $table->unique('code');
        });
    }
};
