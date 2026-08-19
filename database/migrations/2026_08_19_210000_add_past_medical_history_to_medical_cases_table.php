<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 既往史。
 *
 * 病历的叙述部分标准是三段：主诉 / 现病史 / 既往史。此前只有前两段，
 * 既往史（全身病史、过敏史、既往牙科治疗史）没有落脚点 —— 医生只能塞进现病史，
 * 或者干脆不写。同类产品（轻松牙医）的病历首屏就是这三段并列。
 *
 * 注意与 patients 表上那几个字段的区别：patients.systemic_diseases /
 * drug_allergies 是**患者层面**的长期信息，建档时录、之后基本不变；
 * 这里的既往史是**本次就诊时记录的**病史陈述，属于这一份病历的内容，
 * 会随每次就诊而不同，两者不能互相替代。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_cases', 'past_medical_history')) {
            Schema::table('medical_cases', function (Blueprint $table) {
                $table->text('past_medical_history')->nullable()->after('history_of_present_illness');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_cases', 'past_medical_history')) {
            Schema::table('medical_cases', function (Blueprint $table) {
                $table->dropColumn('past_medical_history');
            });
        }
    }
};
