<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockOutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('stock_out_no')->unique();        // 出库单号
            $table->enum('out_type', ['treatment', 'department', 'damage', 'other'])->default('treatment');
            $table->date('stock_out_date');                  // 出库日期
            $table->unsignedBigInteger('patient_id')->nullable();    // 患者ID (诊疗消耗时)
            $table->unsignedBigInteger('appointment_id')->nullable(); // 预约ID (诊疗消耗时)
            $table->string('department')->nullable();        // 科室名称 (科室领用时)
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('appointment_id')->references('id')->on('appointments');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_outs');
    }
}
