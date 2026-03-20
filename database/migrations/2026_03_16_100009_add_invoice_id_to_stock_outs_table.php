<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 出库单新增 invoice_id（前台代销联动）和 stock_insufficient（库存不足标记）字段。
 * AG-049: 通过 invoice_id 唯一索引保证幂等性。
 * AG-051: stock_insufficient 标记库存不足但仍放行的出库单。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->after('appointment_id')
                  ->comment('前台代销：关联的发票 ID，用于幂等检查（AG-049）');
            $table->boolean('stock_insufficient')->default(false)->after('invoice_id')
                  ->comment('库存不足时允许收费但标记此字段（AG-051）');

            $table->unique('invoice_id', 'stock_outs_invoice_id_unique');
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_outs', function (Blueprint $table) {
            $table->dropForeign('stock_outs_invoice_id_foreign');
            $table->dropUnique('stock_outs_invoice_id_unique');
            $table->dropColumn(['invoice_id', 'stock_insufficient']);
        });
    }
};
