<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // stock_out_status
        foreach ([
            ['draft',      '草稿',   1],
            ['confirmed',  '已确认', 2],
            ['cancelled',  '已取消', 3],
        ] as $item) {
            $rows[] = ['type' => 'stock_out_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // stock_out_type
        foreach ([
            ['treatment',  '诊疗用药', 1],
            ['department', '科室领用', 2],
            ['damage',     '损耗报废', 3],
            ['other',      '其他',     4],
        ] as $item) {
            $rows[] = ['type' => 'stock_out_type', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // refund_method
        foreach ([
            ['cash',         '现金',   1],
            ['wechat',       '微信支付', 2],
            ['alipay',       '支付宝', 3],
            ['card',         '银行卡', 4],
            ['stored_value', '储值卡', 5],
        ] as $item) {
            $rows[] = ['type' => 'refund_method', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // refund_approval_status
        foreach ([
            ['pending',  '待审批', 1],
            ['approved', '已批准', 2],
            ['rejected', '已拒绝', 3],
        ] as $item) {
            $rows[] = ['type' => 'refund_approval_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // chair_status
        foreach ([
            ['active',      '正常',   1],
            ['maintenance', '维护中', 2],
            ['offline',     '已停用', 3],
        ] as $item) {
            $rows[] = ['type' => 'chair_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('dict_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('dict_items')->whereIn('type', [
            'stock_out_status',
            'stock_out_type',
            'refund_method',
            'refund_approval_status',
            'chair_status',
        ])->delete();
    }
};
