<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 器械包台账
        Schema::create('sterilization_kits', function (Blueprint $table) {
            $table->id();
            $table->string('kit_no', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('_who_added')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. 器械包明细（无 deleted_at，物理删除）
        Schema::create('sterilization_kit_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('sterilization_kits')->cascadeOnDelete();
            $table->string('instrument_name', 100);
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
        });

        // 3. 灭菌批次
        Schema::create('sterilization_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('sterilization_kits');
            $table->string('batch_no', 50)->unique();
            $table->enum('method', ['autoclave', 'chemical', 'dry_heat']);
            $table->decimal('temperature', 5, 1)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->foreignId('operator_id')->constrained('users');
            $table->dateTime('sterilized_at');
            $table->dateTime('expires_at');
            $table->enum('status', ['valid', 'used', 'voided'])->default('valid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at']); // 过期查询优化
        });

        // 4. 使用记录（追溯）
        Schema::create('sterilization_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('sterilization_records');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('used_by')->constrained('users');
            $table->dateTime('used_at');
            $table->text('notes')->nullable();
            // 冗余快照字段
            $table->string('patient_name', 100)->nullable();
            $table->string('doctor_name', 100)->nullable();
            $table->string('kit_name', 100)->nullable();
            $table->string('batch_no', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sterilization_usages');
        Schema::dropIfExists('sterilization_records');
        Schema::dropIfExists('sterilization_kit_instruments');
        Schema::dropIfExists('sterilization_kits');
    }
};
