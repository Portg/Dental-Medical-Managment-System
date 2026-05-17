<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 口腔门诊工作日志（纸质台账 OCR 补录）。
 * 每行一条就诊记录；patient_id / invoice_id 可空（可选关联、可选轻量发票）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->nullable();
            $table->string('patient_name', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('age', 20)->nullable();
            $table->string('visit_type', 20)->nullable(); // initial / revisit
            $table->string('phone', 30)->nullable();
            $table->string('tooth_position', 100)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();
            $table->string('doctor_name_raw', 50)->nullable();
            $table->decimal('amount', 10, 2)->nullable();

            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->string('source_image')->nullable();
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('_who_added')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('log_date');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
