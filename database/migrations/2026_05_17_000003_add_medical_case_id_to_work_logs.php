<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('medical_case_id')->nullable()->after('invoice_id');
            $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropForeign(['medical_case_id']);
            $table->dropColumn('medical_case_id');
        });
    }
};
