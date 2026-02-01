<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickPhraseCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quick_phrase_categories', function (Blueprint $table) {
            $table->bigIncrements('id');

            // === 业务字段 ===
            $table->string('name');                          // 分类名称
            $table->text('description')->nullable();         // 分类描述
            $table->integer('display_order')->default(0);    // 排序序号
            $table->boolean('is_active')->default(true);     // 是否启用

            // === 审计字段 ===
            $table->bigInteger('_who_added')->unsigned()->nullable(); // 创建人

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quick_phrase_categories');
    }
}
