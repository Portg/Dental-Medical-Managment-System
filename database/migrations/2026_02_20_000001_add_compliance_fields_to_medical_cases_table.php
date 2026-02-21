<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            $table->unsignedInteger('version_number')->default(1)->after('modification_reason');
            $table->dateTime('signed_at')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            $table->dropColumn(['version_number', 'signed_at']);
        });
    }
};
