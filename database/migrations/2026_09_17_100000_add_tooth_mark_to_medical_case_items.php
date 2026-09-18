<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 病历行的牙位标记：△ 残根 / ✕ 已拔除 / — 缺失。
 *
 * 部位记录法里，这三个符号写在牙位号的上方或下方，是医生记录牙齿状况的通用写法：
 *
 *     △  残根      牙冠基本没了，只剩牙根
 *     ✕  已拔除    这个位置的牙已经不在，或是计划拔除的坏牙
 *     —  缺失      单独写在一颗牙下时表示该牙缺失
 *
 * 三个都是**单颗牙的状态**，所以一个字段就够。
 *
 * 刻意不做的一件事：「—」连起来画还有第二个含义 —— 几颗牙之间做了连冠的固定假牙
 * （烤瓷桥）。那是跨牙关系，要另一套结构（起止牙位 + 桥体位置）。现在不做，
 * 因为这件事文字里已经写得清楚（短语库「修复」槽位里就有「缺失，固定修复，」），
 * 而为它引入跨牙数据结构，收益远不如三个单牙标记直接。这是有意省略，不是漏掉。
 *
 * 为什么落在行上而不是单颗牙上：一行 = 一条临床陈述。写「16,17 残根」就是两颗
 * 都是残根；若 16 残根而 17 只是龋坏，本来就该分两行 —— 与「一行一条陈述」的
 * 既有约定一致。
 *
 * 为什么不写 dental_charts：那张表存的是牙齿的**当前状态**，应当由历次观察派生，
 * 不是与病历并列的第二个录入口。同一件事两个入口，两处对不上时谁也说不清以哪个
 * 为准（病历页的「治疗项目」刚因为同样的理由被删掉）。让病历去喂那张表是下一步。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_case_items', 'tooth_mark')) {
            Schema::table('medical_case_items', function (Blueprint $table) {
                // 存 slug（residual_root / extracted / missing）而不是符号本身：
                // 符号是显示层的事，中英文界面、打印、导出各有各的呈现。
                $table->string('tooth_mark', 20)->nullable()->after('tooth_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_case_items', 'tooth_mark')) {
            Schema::table('medical_case_items', function (Blueprint $table) {
                $table->dropColumn('tooth_mark');
            });
        }
    }
};
