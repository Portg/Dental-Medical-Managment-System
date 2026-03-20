<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 新处方流程支持独立于预约创建处方/账单，appointment_id 改为可空。
 * - prescriptions.appointment_id: 新流程处方不再绑定预约
 * - invoices.appointment_id: 处方结算生成的账单无预约
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable(false)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->unsignedBigInteger('appointment_id')->nullable(false)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments');
        });
    }
};
