<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prescription-Billing Integration (参考口腔云 3.5.1~3.5.5)
 *
 * 1. medical_services: 新增 is_prescription 标记处方类项目
 * 2. prescription_items: 新增 medical_service_id (项目统一) + unit_price (价格快照)
 * 3. prescriptions: 新增 invoice_id (处方-收费关联, 删除保护依据 AG-023)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. medical_services: 标记哪些收费项目可用于处方
        Schema::table('medical_services', function (Blueprint $table) {
            $table->boolean('is_prescription')->default(false)->after('is_active')
                  ->comment('是否为处方类项目');
        });

        // 2. prescription_items: 关联 medical_services + 价格快照
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->unsignedBigInteger('medical_service_id')->nullable()->after('prescription_id')
                  ->comment('关联收费项目');
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity')
                  ->comment('开方时单价快照');

            $table->foreign('medical_service_id')
                  ->references('id')->on('medical_services')
                  ->onDelete('set null');
        });

        // 3. prescriptions: 关联 Invoice (AG-023 删除保护依据)
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('doctor_id')
                  ->comment('关联账单, 有值时不可删除处方');

            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['medical_service_id']);
            $table->dropColumn(['medical_service_id', 'unit_price']);
        });

        Schema::table('medical_services', function (Blueprint $table) {
            $table->dropColumn('is_prescription');
        });
    }
};
