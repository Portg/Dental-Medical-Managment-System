<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiagnosesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('diagnosis_name');
            $table->string('icd_code')->nullable();
            $table->date('diagnosis_date');
            $table->enum('status', ['Active', 'Resolved', 'Chronic'])->default('Active');
            $table->enum('severity', ['Mild', 'Moderate', 'Severe'])->nullable();
            $table->text('notes')->nullable();
            $table->date('resolved_date')->nullable();

            $table->bigInteger('medical_case_id')->unsigned()->nullable();
            $table->bigInteger('patient_id')->unsigned();
            $table->bigInteger('_who_added')->unsigned();

            $table->foreign('medical_case_id')->references('id')->on('medical_cases');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diagnoses');
    }
}
