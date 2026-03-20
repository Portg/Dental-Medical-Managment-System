<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 补充 medical_cases 复合索引：
 * - (patient_id, case_date)  — 患者病历历史查询
 * - (status, case_date)      — 状态过滤 + 报表
 * - (doctor_id, case_date)   — 医生维度过滤
 *
 * invoices 补充复合索引：
 * - (patient_id, deleted_at) — 患者发票历史查询
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            if (!$this->hasIndex('medical_cases', 'medical_cases_patient_date_index')) {
                $table->index(['patient_id', 'case_date'], 'medical_cases_patient_date_index');
            }
            if (!$this->hasIndex('medical_cases', 'medical_cases_status_date_index')) {
                $table->index(['status', 'case_date'], 'medical_cases_status_date_index');
            }
            if (!$this->hasIndex('medical_cases', 'medical_cases_doctor_date_index')) {
                $table->index(['doctor_id', 'case_date'], 'medical_cases_doctor_date_index');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!$this->hasIndex('invoices', 'invoices_patient_soft_index')) {
                $table->index(['patient_id', 'deleted_at'], 'invoices_patient_soft_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_cases', function (Blueprint $table) {
            $table->dropIndexIfExists('medical_cases_patient_date_index');
            $table->dropIndexIfExists('medical_cases_status_date_index');
            $table->dropIndexIfExists('medical_cases_doctor_date_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndexIfExists('invoices_patient_soft_index');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]))->isNotEmpty();
    }
};
