<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('item_code')->unique();           // 物资编码
            $table->string('name');                          // 物资名称
            $table->string('specification')->nullable();     // 规格型号 (如 20ml/支)
            $table->string('unit');                          // 单位 (支、盒、个等)
            $table->unsignedBigInteger('category_id');       // 分类ID
            $table->string('brand')->nullable();             // 品牌厂家
            $table->decimal('reference_price', 10, 2)->default(0);  // 参考进价
            $table->decimal('selling_price', 10, 2)->default(0);    // 销售价格
            $table->boolean('track_expiry')->default(false); // 是否管理有效期
            $table->integer('stock_warning_level')->default(10);    // 库存预警下限
            $table->string('storage_location')->nullable();  // 存放位置
            $table->decimal('current_stock', 10, 2)->default(0);    // 当前库存
            $table->decimal('average_cost', 10, 2)->default(0);     // 加权平均成本
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('category_id')->references('id')->on('inventory_categories');
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
        Schema::dropIfExists('inventory_items');
    }
}
