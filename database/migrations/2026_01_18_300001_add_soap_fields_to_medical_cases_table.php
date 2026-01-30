<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoapFieldsToMedicalCasesTable extends Migration
{
    /**
     * Run the migrations.
     * Design spec F-MED-001: Additional SOAP format fields
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            // 检查 (O - Objective examination findings)
            if (!Schema::hasColumn('medical_cases', 'examination')) {
                $table->text('examination')->nullable()->after('history_of_present_illness');
            }

            // 检查关联牙位 (JSON array)
            if (!Schema::hasColumn('medical_cases', 'examination_teeth')) {
                $table->json('examination_teeth')->nullable()->after('examination');
            }

            // 诊断文字 (A - Assessment)
            if (!Schema::hasColumn('medical_cases', 'diagnosis')) {
                $table->text('diagnosis')->nullable()->after('auxiliary_examination');
            }

            // 治疗 (P - Plan/Treatment)
            if (!Schema::hasColumn('medical_cases', 'treatment')) {
                $table->text('treatment')->nullable()->after('diagnosis');
            }

            // 治疗关联项目 (JSON array of service IDs)
            if (!Schema::hasColumn('medical_cases', 'treatment_services')) {
                $table->json('treatment_services')->nullable()->after('treatment');
            }

            // 复诊说明
            if (!Schema::hasColumn('medical_cases', 'next_visit_note')) {
                $table->string('next_visit_note', 255)->nullable()->after('next_visit_date');
            }

            // 自动创建复诊提醒
            if (!Schema::hasColumn('medical_cases', 'auto_create_followup')) {
                $table->boolean('auto_create_followup')->default(false)->after('next_visit_note');
            }

            // 草稿状态
            if (!Schema::hasColumn('medical_cases', 'is_draft')) {
                $table->boolean('is_draft')->default(true)->after('status');
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
        Schema::table('medical_cases', function (Blueprint $table) {
            $columns = [
                'examination', 'examination_teeth', 'diagnosis', 'treatment',
                'treatment_services', 'next_visit_note', 'auto_create_followup', 'is_draft'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('medical_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
