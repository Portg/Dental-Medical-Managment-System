<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountManagementToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 4.1.2: 收费开单 - 折扣管理功能
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 会员折扣 (优先级1)
            if (!Schema::hasColumn('invoices', 'member_discount_rate')) {
                $table->decimal('member_discount_rate', 5, 2)->default(0)->after('discount_amount')
                    ->comment('会员折扣率(百分比)');
            }
            if (!Schema::hasColumn('invoices', 'member_discount_amount')) {
                $table->decimal('member_discount_amount', 14, 2)->default(0)->after('member_discount_rate')
                    ->comment('会员折扣金额');
            }

            // 项目折扣 (优先级2)
            if (!Schema::hasColumn('invoices', 'item_discount_amount')) {
                $table->decimal('item_discount_amount', 14, 2)->default(0)->after('member_discount_amount')
                    ->comment('项目折扣金额');
            }

            // 整单折扣 (优先级3)
            if (!Schema::hasColumn('invoices', 'order_discount_rate')) {
                $table->decimal('order_discount_rate', 5, 2)->default(0)->after('item_discount_amount')
                    ->comment('整单折扣率(百分比)');
            }
            if (!Schema::hasColumn('invoices', 'order_discount_amount')) {
                $table->decimal('order_discount_amount', 14, 2)->default(0)->after('order_discount_rate')
                    ->comment('整单折扣金额');
            }

            // 优惠券折扣 (优先级4)
            if (!Schema::hasColumn('invoices', 'coupon_id')) {
                $table->unsignedBigInteger('coupon_id')->nullable()->after('order_discount_amount')
                    ->comment('使用的优惠券ID');
            }
            if (!Schema::hasColumn('invoices', 'coupon_discount_amount')) {
                $table->decimal('coupon_discount_amount', 14, 2)->default(0)->after('coupon_id')
                    ->comment('优惠券折扣金额');
            }

            // 折扣审批 (BR-035: 超过500元折扣需主管审批)
            if (!Schema::hasColumn('invoices', 'discount_approval_status')) {
                $table->enum('discount_approval_status', ['none', 'pending', 'approved', 'rejected'])
                    ->default('none')->after('coupon_discount_amount')
                    ->comment('折扣审批状态');
            }
            if (!Schema::hasColumn('invoices', 'discount_approved_by')) {
                $table->unsignedBigInteger('discount_approved_by')->nullable()->after('discount_approval_status')
                    ->comment('折扣审批人');
            }
            if (!Schema::hasColumn('invoices', 'discount_approved_at')) {
                $table->timestamp('discount_approved_at')->nullable()->after('discount_approved_by')
                    ->comment('折扣审批时间');
            }
            if (!Schema::hasColumn('invoices', 'discount_approval_reason')) {
                $table->string('discount_approval_reason', 500)->nullable()->after('discount_approved_at')
                    ->comment('折扣审批原因/说明');
            }

            // 欠费挂账
            if (!Schema::hasColumn('invoices', 'is_credit')) {
                $table->boolean('is_credit')->default(false)->after('discount_approval_reason')
                    ->comment('是否挂账');
            }
            if (!Schema::hasColumn('invoices', 'credit_approved_by')) {
                $table->unsignedBigInteger('credit_approved_by')->nullable()->after('is_credit')
                    ->comment('挂账审批人');
            }
            if (!Schema::hasColumn('invoices', 'credit_approved_at')) {
                $table->timestamp('credit_approved_at')->nullable()->after('credit_approved_by')
                    ->comment('挂账审批时间');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = [
                'member_discount_rate', 'member_discount_amount',
                'item_discount_amount',
                'order_discount_rate', 'order_discount_amount',
                'coupon_id', 'coupon_discount_amount',
                'discount_approval_status', 'discount_approved_by',
                'discount_approved_at', 'discount_approval_reason',
                'is_credit', 'credit_approved_by', 'credit_approved_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}