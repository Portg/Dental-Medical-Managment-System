<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientAnalyticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_analytics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id')->unique();
            $table->string('source_channel', 100)->nullable();
            $table->date('first_visit_date')->nullable();
            $table->date('last_visit_date')->nullable();
            $table->integer('visit_count')->default(0);
            $table->integer('days_since_last_visit')->default(0);
            $table->boolean('is_repeat_patient')->default(false);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->decimal('avg_transaction_value', 12, 2)->default(0);
            $table->decimal('repeat_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_analytics');
    }
}
