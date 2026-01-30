<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockOutItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_out_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_out_id');      // 出库单ID
            $table->unsignedBigInteger('inventory_item_id'); // 物资ID
            $table->decimal('qty', 10, 2);                   // 数量
            $table->decimal('unit_cost', 10, 2);             // 单位成本
            $table->decimal('amount', 12, 2);                // 金额
            $table->string('batch_no')->nullable();          // 批次号
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('stock_out_id')->references('id')->on('stock_outs')->onDelete('cascade');
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
        Schema::dropIfExists('stock_out_items');
    }
}
