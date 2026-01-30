<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentPlanItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->string('item_name', 200);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('related_teeth')->nullable();
            $table->text('material_details')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('sequence')->default(0);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('treatment_plan_id')->references('id')->on('treatment_plans')->onDelete('cascade');
            $table->foreign('stage_id')->references('id')->on('treatment_plan_stages')->onDelete('set null');
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
        Schema::dropIfExists('treatment_plan_items');
    }
}
