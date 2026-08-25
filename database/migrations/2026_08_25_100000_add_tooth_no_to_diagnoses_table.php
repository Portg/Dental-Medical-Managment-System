<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 诊断加牙位。
 *
 * 背景：病历的「诊断」段此前有两个去处，而且互不相通 ——
 *   medical_cases.diagnosis        主流程（新建病历那一屏）写这里，一段文本
 *   diagnoses 表                   只能从病历详情页的「诊断记录」Tab 单独添加
 *
 * 医生的实际动作是「新建病历 → 一屏填完 → 提交」，不会填完再拐到详情页点一次
 * 「添加诊断」。所以 diagnoses 表一直是 0 条 —— 不是功能不好，是主流程走不到。
 *
 * 现在把诊断段接进主流程：编辑页的诊断行直接写 diagnoses，一行一条诊断。
 * 而 diagnoses 原本没有牙位列（它是按「一个患者有哪些诊断」设计的，不分牙），
 * 口腔科的诊断几乎都是针对具体牙位的（「16 中龋」「36 慢性根尖周炎」），
 * 补上 tooth_no 之后才能承接编辑页那种「牙位 + 文字」的写法。
 *
 * 可空：不针对具体牙位的诊断（「慢性牙周炎」）本来就没有牙位。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('diagnoses', 'tooth_no')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->string('tooth_no', 20)->nullable()->after('diagnosis_name');
                // 「这颗牙历次诊断过什么」—— 与 medical_case_items.tooth_no 同一个用途
                $table->index('tooth_no');
                // 一份病历的诊断按录入顺序展示
                $table->unsignedInteger('sort_order')->default(0)->after('tooth_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('diagnoses', 'tooth_no')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropIndex(['tooth_no']);
                $table->dropColumn(['tooth_no', 'sort_order']);
            });
        }
    }
};
