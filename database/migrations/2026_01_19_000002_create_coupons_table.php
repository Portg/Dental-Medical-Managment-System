<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 4.1.2: 优惠券管理
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique()->comment('优惠券码');
            $table->string('name', 100)->comment('优惠券名称');
            $table->text('description')->nullable()->comment('描述');

            // 优惠券类型
            $table->enum('type', ['fixed', 'percentage'])->default('fixed')
                ->comment('类型: fixed=固定金额, percentage=百分比');
            $table->decimal('value', 14, 2)->comment('优惠值(金额或百分比)');
            $table->decimal('min_order_amount', 14, 2)->default(0)->comment('最低消费金额');
            $table->decimal('max_discount_amount', 14, 2)->nullable()->comment('最大折扣金额(百分比类型时)');

            // 使用限制
            $table->integer('total_quantity')->nullable()->comment('总发行量');
            $table->integer('used_quantity')->default(0)->comment('已使用数量');
            $table->integer('per_user_limit')->default(1)->comment('每人使用次数限制');

            // 适用范围
            $table->json('applicable_services')->nullable()->comment('适用服务ID列表');
            $table->json('applicable_member_levels')->nullable()->comment('适用会员等级');

            // 有效期
            $table->date('start_date')->comment('开始日期');
            $table->date('end_date')->comment('结束日期');

            // 状态
            $table->boolean('is_active')->default(true)->comment('是否启用');

            $table->unsignedBigInteger('branch_id')->nullable()->comment('适用门店');
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        // 优惠券使用记录
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('discount_amount', 14, 2)->comment('实际折扣金额');
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

            $table->index(['coupon_id', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
}