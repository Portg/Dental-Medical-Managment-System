<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceConsumablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_consumables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('medical_service_id'); // 医疗服务ID
            $table->unsignedBigInteger('inventory_item_id');  // 物资ID
            $table->decimal('qty', 10, 2);                    // 消耗数量
            $table->boolean('is_required')->default(true);    // 是否必需
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('medical_service_id')->references('id')->on('medical_services');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
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
        Schema::dropIfExists('service_consumables');
    }
}
