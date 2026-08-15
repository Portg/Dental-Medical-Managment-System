<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * expense_payments.payment_method 从 enum 放开成 varchar。
 *
 * 建表迁移写死了 enum('Cash','Mobile Money','Cheque','Online Wallet')，而支出付款
 * 表单（resources/views/expenses/payment/create.blade.php）给出的第四项是
 * 「Bank Wire Transfer」——枚举里根本没有。选它提交，MySQL 严格模式下直接报错，
 * 非严格模式下则静默写成空串，账上就多一笔付款方式不明的钱。
 *
 * 同类问题 2026_08_11 在 sms_loggings.status 上刚踩过一次：付款方式这种会随业务
 * 增删的取值，用 enum 只是把「改一个字符串」变成「改表结构」，几年后必然再撞。
 * 取值范围交给应用层校验，库这边放开。
 *
 * 反向迁移会把 enum 外的值归到 Cash —— 这是有损的，因此只在确需回滚时使用。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE expense_payments MODIFY payment_method VARCHAR(50) NULL");
    }

    public function down(): void
    {
        DB::table('expense_payments')
            ->whereNotIn('payment_method', ['Cash', 'Mobile Money', 'Cheque', 'Online Wallet'])
            ->whereNotNull('payment_method')
            ->update(['payment_method' => 'Cash']);

        DB::statement(
            "ALTER TABLE expense_payments MODIFY payment_method ENUM('Cash','Mobile Money','Cheque','Online Wallet') NULL"
        );
    }
};
