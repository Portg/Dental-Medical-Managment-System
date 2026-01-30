<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceIdToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     * 表单设计规范 F-APT-001: 预约项目字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // 预约项目 - 关联到medical_services表
            if (!Schema::hasColumn('appointments', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable()->after('chair_id');
                $table->foreign('service_id')->references('id')->on('medical_services')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'service_id')) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            }
        });
    }
}
