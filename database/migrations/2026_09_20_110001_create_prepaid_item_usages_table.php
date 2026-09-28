<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 预收项目的核销台账 —— 「剩余项目」的另一半。
 *
 * 余量不单独存一个数字，而是**每次核销记一笔**，余量由
 *     invoice_items.qty - SUM(prepaid_item_usages.qty)
 * 算出来。理由是余量存成字段就有两个真相：一个人在剩余项目里点了核销、另一
 * 个人把那张账单改了数量，两边谁也说不清以哪个为准。存流水则账单是权威，
 * 核销是对账单的引用，怎么改都能对得上，而且「谁在哪次就诊用掉了一次」
 * 这件事本身就是诊所要查的（患者说「我明明还剩两次」的时候）。
 *
 * 权属落在 invoice_item 上而不是 medical_service 上：同一个项目患者可能买过
 * 两次（去年一张洁牙次卡、今年又一张），价格和折扣都不同。按收费行分开记，
 * 先买的先用完、剩余金额按各自的实收单价算，才对得上账。
 *
 * patient_id 是冗余列：剩余项目永远是「按患者查」，不冗余就得每次
 * join invoices 才能过滤，而这是收费窗口每开一次都要跑的查询。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prepaid_item_usages')) {
            return;
        }

        Schema::create('prepaid_item_usages', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 来源：哪一笔付费。余量就是从这一行的 qty 里扣的
            $table->unsignedBigInteger('invoice_item_id');
            $table->unsignedBigInteger('patient_id');

            // 小数而不是整数：正畸这类按「期」记的，半期、调整次数都可能不是整数
            $table->decimal('qty', 8, 2);
            $table->date('used_at');

            // 用在哪次就诊上。都可空：手工补记一次核销时未必有对应的就诊记录
            $table->unsignedBigInteger('medical_case_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();

            $table->string('notes', 255)->nullable();
            $table->unsignedBigInteger('_who_added');

            $table->timestamps();
            $table->softDeletes();

            // 算余量的主查询：按患者取所有核销，再按收费行汇总
            $table->index(['patient_id', 'deleted_at']);
            $table->index('invoice_item_id');

            $table->foreign('invoice_item_id')->references('id')->on('invoice_items');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('_who_added')->references('id')->on('users');
            // 就诊相关的三个外键刻意不建：这三张表都有软删除，历史数据里
            // 被删掉的就诊仍应保留核销记录（钱已经收了，服务也做了）
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prepaid_item_usages');
    }
};
