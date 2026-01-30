<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('treatment_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('medical_case_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('material_name', 200);
            $table->string('material_code', 50)->nullable();
            $table->string('related_tooth_number', 20)->nullable();
            $table->unsignedBigInteger('dental_chart_id')->nullable();
            $table->string('material_type', 50)->nullable();
            $table->decimal('quantity_used', 12, 2)->default(1);
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('medical_case_id')->references('id')->on('medical_cases');
            $table->foreign('appointment_id')->references('id')->on('appointments');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('dental_chart_id')->references('id')->on('dental_charts');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
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
        Schema::dropIfExists('treatment_materials');
    }
}
