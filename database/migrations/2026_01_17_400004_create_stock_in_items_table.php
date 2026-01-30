<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockInItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_in_id');       // 入库单ID
            $table->unsignedBigInteger('inventory_item_id'); // 物资ID
            $table->decimal('qty', 10, 2);                   // 数量
            $table->decimal('unit_price', 10, 2);            // 单价
            $table->decimal('amount', 12, 2);                // 金额 (qty * unit_price)
            $table->string('batch_no')->nullable();          // 批次号
            $table->date('expiry_date')->nullable();         // 有效期
            $table->date('production_date')->nullable();     // 生产日期
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('stock_in_id')->references('id')->on('stock_ins')->onDelete('cascade');
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
        Schema::dropIfExists('stock_in_items');
    }
}
