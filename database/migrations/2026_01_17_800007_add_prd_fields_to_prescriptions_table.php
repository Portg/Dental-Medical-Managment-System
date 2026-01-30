<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToPrescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 处方表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // 处方编号
            if (!Schema::hasColumn('prescriptions', 'prescription_no')) {
                $table->string('prescription_no', 50)->nullable()->unique()->after('id');
            }

            // 病历ID
            if (!Schema::hasColumn('prescriptions', 'medical_case_id')) {
                $table->unsignedBigInteger('medical_case_id')->nullable()->after('appointment_id');
                $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('set null');
            }

            // 患者ID
            if (!Schema::hasColumn('prescriptions', 'patient_id')) {
                $table->unsignedBigInteger('patient_id')->nullable()->after('medical_case_id');
                $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            }

            // 医生ID
            if (!Schema::hasColumn('prescriptions', 'doctor_id')) {
                $table->unsignedBigInteger('doctor_id')->nullable()->after('patient_id');
                $table->foreign('doctor_id')->references('id')->on('users')->onDelete('set null');
            }

            // 处方状态
            if (!Schema::hasColumn('prescriptions', 'status')) {
                $table->enum('status', ['pending', 'filled', 'completed', 'discontinued', 'on_hold'])->default('pending')->after('doctor_id');
            }

            // 开具日期
            if (!Schema::hasColumn('prescriptions', 'prescription_date')) {
                $table->date('prescription_date')->nullable()->after('status');
            }

            // 有效期
            if (!Schema::hasColumn('prescriptions', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('prescription_date');
            }

            // 允许续药次数
            if (!Schema::hasColumn('prescriptions', 'refills_allowed')) {
                $table->integer('refills_allowed')->default(0)->after('expiry_date');
            }

            // 已续药次数
            if (!Schema::hasColumn('prescriptions', 'refills_used')) {
                $table->integer('refills_used')->default(0)->after('refills_allowed');
            }

            // 医生签名
            if (!Schema::hasColumn('prescriptions', 'doctor_signature')) {
                $table->text('doctor_signature')->nullable()->after('refills_used');
            }

            // 备注
            if (!Schema::hasColumn('prescriptions', 'notes')) {
                $table->text('notes')->nullable()->after('doctor_signature');
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
        Schema::table('prescriptions', function (Blueprint $table) {
            $foreignKeys = ['medical_case_id', 'patient_id', 'doctor_id'];
            foreach ($foreignKeys as $fk) {
                if (Schema::hasColumn('prescriptions', $fk)) {
                    $table->dropForeign([$fk]);
                }
            }

            $columns = [
                'prescription_no', 'medical_case_id', 'patient_id', 'doctor_id',
                'status', 'prescription_date', 'expiry_date',
                'refills_allowed', 'refills_used', 'doctor_signature', 'notes'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('prescriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
