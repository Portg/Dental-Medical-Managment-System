<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 病历补「治疗计划」段。
 *
 * 参考产品的病历是五段：检查 / 其他检查 / 诊断 / 治疗计划 / 治疗。SOAP 的 P 被拆成
 * 两件事 —— 「打算做什么」（活动义齿修复）与「这次实际做了什么」（制取上下颌藻酸盐
 * 印模）。活动义齿要来好几次：计划跨次不变，处置每次不同。修复、正畸、种植这类
 * 疗程性治疗，不拆开就只能把两者揉进同一段文字里。
 *
 * 与 treatment_plans 表不是一回事，别混：那张表是报价单 + 风险告知 + 患者电子签名，
 * 是商务与法律文书；这里是 SOAP 的 P，是临床叙述。
 *
 * 列本身由 medical_case_items 的行派生（与 examination / treatment 同一机制），
 * 保留是因为打印、病历详情、API、OCR、工作日志五处都在消费这些文本列。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_cases', 'treatment_plan')) {
            Schema::table('medical_cases', function (Blueprint $table) {
                // 放在 diagnosis 之后、treatment 之前 —— 与病历上的段落顺序一致，
                // 以后 DESCRIBE 这张表时读起来就是病历本身的顺序。
                $table->text('treatment_plan')->nullable()->after('diagnosis');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_cases', 'treatment_plan')) {
            Schema::table('medical_cases', function (Blueprint $table) {
                $table->dropColumn('treatment_plan');
            });
        }
    }
};
