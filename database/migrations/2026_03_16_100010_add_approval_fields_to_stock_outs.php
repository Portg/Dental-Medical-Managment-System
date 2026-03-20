<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 出库单新增审批相关字段，支持医生申领单审批流程。
 * AG-052: pending_approval 状态由业务层控制，不修改 DB enum（out_type/status 为 string，DictItem 驱动）。
 * AG-053: approved_by 后端取 Auth::id()，与 _who_added 比较。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->string('recipient')->nullable()->after('department')
                  ->comment('领用人姓名（申领单）');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('recipient')
                  ->comment('退货供应商 ID（供应商退货类型）');
            $table->unsignedBigInteger('approved_by')->nullable()->after('supplier_id')
                  ->comment('审批人 user_id（AG-053）');
            $table->timestamp('approved_at')->nullable()->after('approved_by')
                  ->comment('审批时间');
        });
    }

    public function down(): void
    {
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->dropColumn(['recipient', 'supplier_id', 'approved_by', 'approved_at']);
        });
    }
};
