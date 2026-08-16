<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 患者建档日期独立成列。
 *
 * 此前「建档日期」用的就是 created_at：新建时自动取当天，改不了。诊所把纸质档案
 * 或旧系统的患者补录进来时，全部记成录入当天 —— 而患者列表的日期筛选、新增患者
 * 报表都是按这一列算的，于是「本月新增 200 人」里混着二十年前建档的老患者，
 * 报表从补录那天起就再也对不上了。
 *
 * created_at 保持纯审计时间戳（这条记录什么时候进的系统），不给人工改；
 * 业务意义上的建档日期由 registered_at 承担，可以补录。
 *
 * 历史数据回填成 created_at 的日期部分：那正是它们当初的建档日期，
 * 回填之后筛选与报表的口径不变。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('patients', 'registered_at')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->date('registered_at')->nullable()->after('date_of_birth');
            // 患者列表按它做区间筛选，数据量上来之后没索引会全表扫
            $table->index('registered_at');
        });

        DB::statement('UPDATE patients SET registered_at = DATE(created_at) WHERE registered_at IS NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('patients', 'registered_at')) {
            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['registered_at']);
            $table->dropColumn('registered_at');
        });
    }
};
