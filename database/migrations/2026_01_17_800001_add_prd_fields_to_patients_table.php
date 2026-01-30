<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 患者档案表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            // 患者编码 (唯一格式)
            if (!Schema::hasColumn('patients', 'patient_code')) {
                $table->string('patient_code', 50)->nullable()->unique()->after('id');
            }

            // 患者状态
            if (!Schema::hasColumn('patients', 'status')) {
                $table->enum('status', ['active', 'merged', 'archived'])->default('active')->after('patient_code');
            }

            // 合并到患者ID (用于患者合并功能)
            if (!Schema::hasColumn('patients', 'merged_to_id')) {
                $table->unsignedBigInteger('merged_to_id')->nullable()->after('status');
                $table->foreign('merged_to_id')->references('id')->on('patients')->onDelete('set null');
            }

            // 出生日期 (PRD要求)
            if (!Schema::hasColumn('patients', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }

            // 全身疾病史 (JSON格式支持多个)
            if (!Schema::hasColumn('patients', 'systemic_diseases')) {
                $table->json('systemic_diseases')->nullable()->after('medication_history');
            }

            // 患者标签 (JSON格式)
            if (!Schema::hasColumn('patients', 'tags')) {
                $table->json('tags')->nullable()->after('systemic_diseases');
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
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'merged_to_id')) {
                $table->dropForeign(['merged_to_id']);
                $table->dropColumn('merged_to_id');
            }
            if (Schema::hasColumn('patients', 'patient_code')) {
                $table->dropColumn('patient_code');
            }
            if (Schema::hasColumn('patients', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('patients', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('patients', 'systemic_diseases')) {
                $table->dropColumn('systemic_diseases');
            }
            if (Schema::hasColumn('patients', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
}
