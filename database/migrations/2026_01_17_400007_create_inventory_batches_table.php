<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_item_id'); // 物资ID
            $table->string('batch_no');                      // 批次号
            $table->date('expiry_date')->nullable();         // 有效期
            $table->date('production_date')->nullable();     // 生产日期
            $table->decimal('qty', 10, 2);                   // 批次数量
            $table->decimal('unit_cost', 10, 2);             // 批次成本
            $table->unsignedBigInteger('stock_in_id');       // 来源入库单
            $table->enum('status', ['available', 'expired', 'depleted'])->default('available');
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('stock_in_id')->references('id')->on('stock_ins');
            $table->foreign('_who_added')->references('id')->on('users');
            $table->index(['inventory_item_id', 'batch_no']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_batches');
    }
}
