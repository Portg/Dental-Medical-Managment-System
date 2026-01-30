<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorPerformanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_performance', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('doctor_id');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->integer('new_patient_count')->default(0);
            $table->decimal('avg_transaction_value', 12, 2)->default(0);
            $table->decimal('total_commission', 14, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('achievement_rate', 5, 2)->default(0);
            $table->decimal('target_revenue', 14, 2)->default(0);
            $table->json('revenue_by_service')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->unique(['doctor_id', 'period_start_date', 'period_end_date'], 'doctor_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_performance');
    }
}
