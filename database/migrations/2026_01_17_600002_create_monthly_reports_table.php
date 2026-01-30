<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('report_year_month', 7); // Format: YYYY-MM
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('previous_month_revenue', 14, 2)->default(0);
            $table->decimal('same_month_last_year_revenue', 14, 2)->default(0);
            $table->integer('new_patient_count')->default(0);
            $table->integer('repeat_patient_count')->default(0);
            $table->decimal('avg_transaction_value', 12, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('no_show_rate', 5, 2)->default(0);
            $table->json('top_services')->nullable();
            $table->json('doctor_rankings')->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->unique(['report_year_month', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monthly_reports');
    }
}
