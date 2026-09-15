<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 快捷短语加「语义槽位」。
 *
 * 原来短语只按 category 平铺成三个大类（检查 / 诊断 / 治疗）—— 能查到词，但不教人
 * 怎么把一句话说完整。参考产品的做法是按**句子的语义槽位**分组：现病史那一栏是
 * 「时间 → 部位 → 症状 → 治疗情况 → 症状变化 → 口腔习惯」，医生顺着点下来，
 * 「2月前 · 左上后牙 · 因松动拔除 · 现来本院治疗，」自然成句；检查那一栏换成临床
 * 检查的知识结构（龋坏 / 非龋性 / 旧充填 / 牙周 / 修复 / 咬合 / 关节）。
 *
 * 两个配套约定：
 *
 * 1. 短语自带标点。「本院治疗，」「未治疗。」点完直接连成句，不用手动补标点 ——
 *    原来存的是裸词，连点两条会粘在一起。
 * 2. {} 是光标位。「PD={}mm，」插入后光标落在 {} 处，医生只补数值。
 *    刻意不用 __：那个在病历模板里已经表示「替换成本行牙位」，两个含义会打架。
 *
 * category 不另开新列：它本来就是「属于病历的哪一段」，扩它的取值即可
 * （新增 chief_complaint / present_illness / past_history / auxiliary_examination /
 * treatment_plan / medical_orders）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_phrases', function (Blueprint $table) {
            if (!Schema::hasColumn('quick_phrases', 'slot')) {
                // 槽位名直接存中文：短语本身就是中文临床内容，为几十个只跟中文短语
                // 并排出现的组名建翻译键，是成本没有收益。
                $table->string('slot', 40)->nullable()->after('category');
            }
            if (!Schema::hasColumn('quick_phrases', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('slot');
            }
        });

        // 槽位内排序要稳定：同一槽位里「1天前」必须排在「1周前」前面，
        // 靠 id 只能保证插入顺序，改一条就乱。
        Schema::table('quick_phrases', function (Blueprint $table) {
            $table->index(['category', 'slot', 'sort_order'], 'quick_phrases_cat_slot_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quick_phrases', function (Blueprint $table) {
            $table->dropIndex('quick_phrases_cat_slot_sort_idx');
        });

        Schema::table('quick_phrases', function (Blueprint $table) {
            foreach (['slot', 'sort_order'] as $col) {
                if (Schema::hasColumn('quick_phrases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
