<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('image_no')->unique();
            $table->string('title');
            $table->enum('image_type', ['X-Ray', 'CT', 'Intraoral', 'Extraoral', 'Other'])->default('Other');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('description')->nullable();
            $table->string('tooth_number')->nullable();
            $table->date('image_date');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('medical_case_id')->nullable();
            $table->unsignedBigInteger('_who_added')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('set null');
            $table->foreign('_who_added')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_images');
    }
}
