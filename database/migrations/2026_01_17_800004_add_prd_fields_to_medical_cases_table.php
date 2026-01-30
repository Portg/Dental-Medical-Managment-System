<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToMedicalCasesTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 医学病例表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            // 关联牙位 (JSON数组)
            if (!Schema::hasColumn('medical_cases', 'related_teeth')) {
                $table->json('related_teeth')->nullable()->after('chief_complaint');
            }

            // 关联影像 (JSON数组存储image IDs)
            if (!Schema::hasColumn('medical_cases', 'related_images')) {
                $table->json('related_images')->nullable()->after('related_teeth');
            }

            // ICD-10诊断代码
            if (!Schema::hasColumn('medical_cases', 'diagnosis_code')) {
                $table->string('diagnosis_code', 50)->nullable()->after('related_images');
            }

            // 辅助检查
            if (!Schema::hasColumn('medical_cases', 'auxiliary_examination')) {
                $table->text('auxiliary_examination')->nullable()->after('diagnosis_code');
            }

            // 医嘱
            if (!Schema::hasColumn('medical_cases', 'medical_orders')) {
                $table->text('medical_orders')->nullable()->after('auxiliary_examination');
            }

            // 下次复诊日期
            if (!Schema::hasColumn('medical_cases', 'next_visit_date')) {
                $table->date('next_visit_date')->nullable()->after('medical_orders');
            }

            // 就诊类型
            if (!Schema::hasColumn('medical_cases', 'visit_type')) {
                $table->enum('visit_type', ['initial', 'revisit'])->default('initial')->after('next_visit_date');
            }

            // 电子签名
            if (!Schema::hasColumn('medical_cases', 'signature')) {
                $table->text('signature')->nullable()->after('visit_type');
            }

            // 锁定时间 (提交后锁定)
            if (!Schema::hasColumn('medical_cases', 'locked_at')) {
                $table->dateTime('locked_at')->nullable()->after('signature');
            }

            // 最后修改时间
            if (!Schema::hasColumn('medical_cases', 'modified_at')) {
                $table->dateTime('modified_at')->nullable()->after('locked_at');
            }

            // 修改人
            if (!Schema::hasColumn('medical_cases', 'modified_by')) {
                $table->unsignedBigInteger('modified_by')->nullable()->after('modified_at');
                $table->foreign('modified_by')->references('id')->on('users')->onDelete('set null');
            }

            // 修改原因 (24小时后修改需填写)
            if (!Schema::hasColumn('medical_cases', 'modification_reason')) {
                $table->text('modification_reason')->nullable()->after('modified_by');
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
            if (Schema::hasColumn('medical_cases', 'modified_by')) {
                $table->dropForeign(['modified_by']);
            }

            $columns = [
                'related_teeth', 'related_images', 'diagnosis_code',
                'auxiliary_examination', 'medical_orders', 'next_visit_date',
                'visit_type', 'signature', 'locked_at', 'modified_at',
                'modified_by', 'modification_reason'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('medical_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
