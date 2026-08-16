<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 标记这笔收款有没有发过会员权益（累计消费 + 积分）。
 *
 * 撤销收款时要把当初发出去的累计消费冲回来，但「当初发没发」不能靠猜：
 *
 *   - 本次改动之前，走 /payments 的单笔收款**从来不加**累计消费，只有混合支付加；
 *   - 积分有流水可查（member_transactions.invoice_payment_id），累计消费没有；
 *   - 没有标记的话，撤销一笔历史单笔收款会把患者原本就有的累计消费白白减掉，
 *     进而可能把会员等级也降下去。
 *
 * 所以默认 0：历史收款一律视为「没发过」，撤销时不碰累计消费。只有本次改动之后
 * 新建的收款才置 1。
 *
 * 已知的取舍：历史的**混合支付**其实是加过累计消费的，但那批记录与历史单笔收款
 * 在表上分不出来（旧的积分流水只挂 invoice_id、不挂收款 id），一并按「没发过」
 * 处理。结果是撤销这类历史收款时少冲一点，而不是错冲 —— 宁可账面偏大也不要把
 * 患者已有的累计消费凭空减掉。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoice_payments', 'member_benefits_awarded')) {
            return;
        }

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->boolean('member_benefits_awarded')->default(false)->after('amount');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('invoice_payments', 'member_benefits_awarded')) {
            return;
        }

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn('member_benefits_awarded');
        });
    }
};
