<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentPlanStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('treatment_plan_stages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('treatment_plan_id');
            $table->integer('stage_number');
            $table->string('stage_name', 200);
            $table->text('description')->nullable();
            $table->date('estimated_start_date')->nullable();
            $table->date('estimated_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('treatment_plan_id')->references('id')->on('treatment_plans')->onDelete('cascade');
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
        Schema::dropIfExists('treatment_plan_stages');
    }
}
