<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToDentalChartsTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 牙位图表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dental_charts', function (Blueprint $table) {
            // 牙位编号 (FDI标记法: 11-48永久牙, 51-85乳牙)
            if (!Schema::hasColumn('dental_charts', 'tooth_number')) {
                $table->string('tooth_number', 10)->nullable()->after('tooth');
            }

            // 牙齿类型
            if (!Schema::hasColumn('dental_charts', 'tooth_type')) {
                $table->enum('tooth_type', ['permanent', 'primary', 'mixed'])->default('permanent')->after('tooth_number');
            }

            // 牙位状态 (PRD要求的状态枚举)
            if (!Schema::hasColumn('dental_charts', 'tooth_status')) {
                $table->enum('tooth_status', [
                    'normal', 'caries', 'filled', 'crown', 'rct',
                    'missing', 'implant', 'pontic', 'extraction_planned', 'impacted'
                ])->default('normal')->after('tooth_type');
            }

            // 关联病历ID
            if (!Schema::hasColumn('dental_charts', 'medical_case_id')) {
                $table->unsignedBigInteger('medical_case_id')->nullable()->after('appointment_id');
                $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('set null');
            }

            // 负责医生
            if (!Schema::hasColumn('dental_charts', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('medical_case_id');
                $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
            }

            // 状态变更时间
            if (!Schema::hasColumn('dental_charts', 'changed_at')) {
                $table->dateTime('changed_at')->nullable()->after('doctor_id');
            }

            // 变更人
            if (!Schema::hasColumn('dental_charts', 'changed_by')) {
                $table->unsignedBigInteger('changed_by')->nullable()->after('changed_at');
                $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
            }

            // 备注
            if (!Schema::hasColumn('dental_charts', 'notes')) {
                $table->text('notes')->nullable()->after('changed_by');
            }

            // 牙面 (occlusal, buccal, lingual等)
            if (!Schema::hasColumn('dental_charts', 'surface')) {
                $table->string('surface', 50)->nullable()->after('notes');
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
        Schema::table('dental_charts', function (Blueprint $table) {
            $foreignKeys = ['medical_case_id', 'doctor_id', 'changed_by'];
            foreach ($foreignKeys as $fk) {
                if (Schema::hasColumn('dental_charts', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }

            $columns = [
                'tooth_number', 'tooth_type', 'tooth_status',
                'medical_case_id', 'doctor_id', 'changed_at',
                'changed_by', 'notes', 'surface'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('dental_charts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
