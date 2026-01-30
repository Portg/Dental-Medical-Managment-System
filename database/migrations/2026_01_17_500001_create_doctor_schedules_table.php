<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('doctor_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_pattern', ['daily', 'weekly', 'monthly'])->nullable();
            $table->date('recurring_until')->nullable();
            $table->integer('max_patients')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('doctor_id')->references('id')->on('users');
            $table->foreign('changed_by')->references('id')->on('users');
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->index(['doctor_id', 'schedule_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_schedules');
    }
}
