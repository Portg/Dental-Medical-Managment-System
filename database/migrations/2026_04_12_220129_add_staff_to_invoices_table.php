<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('medical_case_id');
                $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'nurse_id')) {
                $table->unsignedBigInteger('nurse_id')->nullable()->after('doctor_id');
                $table->foreign('nurse_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'assistant_id')) {
                $table->unsignedBigInteger('assistant_id')->nullable()->after('nurse_id');
                $table->foreign('assistant_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
            if (Schema::hasColumn('invoices', 'nurse_id')) {
                $table->dropForeign(['nurse_id']);
                $table->dropColumn('nurse_id');
            }
            if (Schema::hasColumn('invoices', 'assistant_id')) {
                $table->dropForeign(['assistant_id']);
                $table->dropColumn('assistant_id');
            }
        });
    }
};
