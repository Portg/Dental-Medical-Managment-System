<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // lab_case_status
        foreach ([
            ['pending',       '待送出', 1],
            ['sent',          '已送出', 2],
            ['in_production', '制作中', 3],
            ['returned',      '已返回', 4],
            ['try_in',        '试戴',   5],
            ['completed',     '完成',   6],
            ['rework',        '返工',   7],
        ] as $item) {
            $rows[] = ['type' => 'lab_case_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // lab_case_prosthesis_type
        foreach ([
            ['crown',          '冠',        1],
            ['bridge',         '桥',        2],
            ['removable',      '活动义齿',   3],
            ['implant',        '种植体',     4],
            ['veneer',         '贴面',       5],
            ['inlay_onlay',    '嵌体/高嵌体', 6],
            ['denture',        '全口义齿',   7],
            ['orthodontic',    '正畸器',     8],
            ['night_guard',    '夜磨牙垫',   9],
            ['surgical_guide', '种植导板',   10],
            ['other',          '其他',       11],
        ] as $item) {
            $rows[] = ['type' => 'lab_case_prosthesis_type', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // lab_case_material
        foreach ([
            ['zirconia',    '氧化锆',   1],
            ['pfm',         '金属烤瓷', 2],
            ['all_ceramic', '全瓷',     3],
            ['emax',        'E.max 铸瓷', 4],
            ['composite',   '树脂',     5],
            ['metal',       '金属',     6],
            ['acrylic',     '丙烯酸',   7],
            ['titanium',    '钛合金',   8],
            ['peek',        'PEEK',     9],
            ['other',       '其他',     10],
        ] as $item) {
            $rows[] = ['type' => 'lab_case_material', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // invoice_payment_method
        foreach ([
            ['Cash',         '现金',    1],
            ['WeChat',       '微信支付', 2],
            ['Alipay',       '支付宝',  3],
            ['BankCard',     '银行卡',  4],
            ['StoredValue',  '储值',    5],
            ['Insurance',    '保险',    6],
            ['Credit',       '赊账',    7],
            ['Self Account', '自账户',  8],
            ['Online Wallet','线上钱包', 9],
            ['Mobile Money', '移动支付', 10],
            ['Cheque',       '支票',    11],
        ] as $item) {
            $rows[] = ['type' => 'invoice_payment_method', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('dict_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('dict_items')->whereIn('type', [
            'lab_case_status',
            'lab_case_prosthesis_type',
            'lab_case_material',
            'invoice_payment_method',
        ])->delete();
    }
};
