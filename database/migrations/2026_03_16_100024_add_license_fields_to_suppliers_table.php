<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 供应商证照字段扩展：
 * - business_license_no  营业执照号
 * - license_expiry_date  证照有效期
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('business_license_no', 100)->nullable()->after('notes');
            $table->date('license_expiry_date')->nullable()->after('business_license_no');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['business_license_no', 'license_expiry_date']);
        });
    }
};
