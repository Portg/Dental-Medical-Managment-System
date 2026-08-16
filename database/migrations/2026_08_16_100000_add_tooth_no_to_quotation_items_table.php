<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 报价单明细补上牙位列。
 *
 * 界面上三处都在收这个字段，库里却从来没有过：
 *   - quotations/create.blade.php 与 index.blade.php 提交 addmore[N][tooth_no]
 *   - quotations/show/edit_quotation.blade.php 的弹窗有 name="tooth_no"
 *   - quotations/show/index.blade.php 的表头有「牙位」，
 *     quotations_show_index.js 声明了 {data: 'tooth_no'}
 * 而 print_quotation.blade.php 里那句 `$row->tooth_no ?? ''` 的注释已经写明
 * 「表上没有这一列」—— 也就是牙位一路被静默丢弃，打印出来永远是空的。
 *
 * 更要命的是明细表：DataTables 拿不到声明过的 tooth_no 会直接抛
 * 「Requested unknown parameter」，报价单详情页只要有一条明细就弹错。
 * 补列比删掉这四处 UI 更合理 —— 牙科报价按牙位报是常规做法。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quotation_items', 'tooth_no')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('tooth_no', 50)->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('quotation_items', 'tooth_no')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('tooth_no');
        });
    }
};
