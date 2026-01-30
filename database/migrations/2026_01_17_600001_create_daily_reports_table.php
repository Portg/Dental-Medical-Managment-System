<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('report_date');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('net_revenue', 14, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->integer('no_show_count')->default(0);
            $table->integer('new_patient_count')->default(0);
            $table->integer('appointment_count')->default(0);
            $table->json('revenue_by_category')->nullable();
            $table->json('revenue_by_payment_method')->nullable();
            $table->json('doctor_performance')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->unique(['report_date', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_reports');
    }
}
