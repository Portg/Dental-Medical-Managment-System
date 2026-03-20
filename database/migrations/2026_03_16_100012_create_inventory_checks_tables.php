<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 创建盘点单及明细表。
 *
 * AG-066 决策说明：
 * 规格要求对 (category_id, check_date) 建唯一约束以防并发重建。
 * 但由于以下原因，不在数据库层加 UNIQUE 索引：
 *   1. MySQL 5.7 不支持局部索引（partial index），无法排除 deleted_at IS NOT NULL 的软删除行；
 *   2. 已确认（confirmed）的盘点单删除后应允许同分类同日期重建；
 * 因此改为应用层（Service）在 INSERT 前查询 status=draft 的同分类同日期记录，
 * 提供友好错误提示（AG-066 应用层检查）。
 * 并发场景下可通过数据库事务 + select 幂等来保证一致性。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('check_no', 50)->unique()->comment('盘点单号，格式 IC+Ymd+4位序号');
            $table->unsignedBigInteger('category_id')->comment('盘点分类');
            $table->date('check_date')->comment('盘点日期');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable()->comment('盘点人（确认时填写）');
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('_who_added')->comment('创建人');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('inventory_categories');
            $table->foreign('checked_by')->references('id')->on('users');
            $table->foreign('_who_added')->references('id')->on('users');

            // 说明：不加 DB-level UNIQUE(category_id, check_date)，见文件头注释（AG-066）
            $table->index(['category_id', 'check_date'], 'idx_check_category_date');
        });

        Schema::create('inventory_check_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_check_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('system_qty', 10, 2)->comment('创建时从 current_stock 快照');
            $table->decimal('actual_qty', 10, 2)->nullable()->comment('用户填写实际库存');
            $table->decimal('diff_qty', 10, 2)->nullable()->comment('actual_qty - system_qty');
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_check_id')
                  ->references('id')->on('inventory_checks')
                  ->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('_who_added')->references('id')->on('users');

            $table->index(['inventory_check_id'], 'idx_check_items_check_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
        Schema::dropIfExists('inventory_checks');
    }
};
