<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 给 lab_case_items 补 deleted_at 软删除列。
 *
 * 建表迁移 2026_03_06_100001 只写了 timestamps()，漏了 softDeletes()，
 * 但 App\LabCaseItem 声明了 use SoftDeletes —— 模型的任何查询都会带上
 * `where deleted_at is null`，导致技工单详情/列表直接抛
 * SQLSTATE[42S22] Unknown column 'lab_case_items.deleted_at'。
 *
 * 同时 LabCaseService::updateLabCase() 重建明细时走的是 LabCaseItem::delete()，
 * 没有该列时删除也不会真正生效。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lab_case_items', 'deleted_at')) {
            return;
        }

        Schema::table('lab_case_items', function (Blueprint $table) {
            $table->softDeletes(); // 软删除时间
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('lab_case_items', 'deleted_at')) {
            return;
        }

        Schema::table('lab_case_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
