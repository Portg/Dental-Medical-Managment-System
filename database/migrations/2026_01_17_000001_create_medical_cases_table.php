<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicalCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_cases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('case_no')->unique();
            $table->string('title');
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->enum('status', ['Open', 'Closed', 'Follow-up'])->default('Open');
            $table->date('case_date');
            $table->date('closed_date')->nullable();
            $table->text('closing_notes')->nullable();

            $table->bigInteger('patient_id')->unsigned();
            $table->bigInteger('doctor_id')->unsigned()->nullable();
            $table->bigInteger('_who_added')->unsigned();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('doctor_id')->references('id')->on('users');
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
        Schema::dropIfExists('medical_cases');
    }
}
