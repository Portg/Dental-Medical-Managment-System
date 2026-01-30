<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientFollowupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_followups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('followup_no')->unique();
            $table->enum('followup_type', ['Phone', 'SMS', 'Email', 'Visit', 'Other'])->default('Phone');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['Pending', 'Completed', 'Cancelled', 'No Response'])->default('Pending');
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->date('next_followup_date')->nullable();
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
        Schema::dropIfExists('patient_followups');
    }
}
