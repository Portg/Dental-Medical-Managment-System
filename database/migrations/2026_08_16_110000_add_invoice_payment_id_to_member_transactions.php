<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 会员流水挂上收款记录，撤销收款时才能精确冲销。
 *
 * 此前积分是「一批收款记一条流水」（processMixedPayment 把整批的积分加总后写一条），
 * 而撤销是按单笔收款做的 —— 想回退某一笔给了多少积分，只能拿费率重算，费率却可能
 * 因为会员等级变动而与当初不同，冲销金额就会对不上。
 *
 * 挂上 invoice_payment_id 之后，积分与储值消费都按笔记账，撤销时按这一列反查，
 * 冲销的就是当初真给出去的那个数。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('member_transactions', 'invoice_payment_id')) {
            return;
        }

        Schema::table('member_transactions', function (Blueprint $table) {
            // 允许为空：储值充值、手工调整这类流水本来就不对应任何一笔收款
            $table->unsignedBigInteger('invoice_payment_id')->nullable()->after('invoice_id');
            $table->index('invoice_payment_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('member_transactions', 'invoice_payment_id')) {
            return;
        }

        Schema::table('member_transactions', function (Blueprint $table) {
            $table->dropIndex(['invoice_payment_id']);
            $table->dropColumn('invoice_payment_id');
        });
    }
};
