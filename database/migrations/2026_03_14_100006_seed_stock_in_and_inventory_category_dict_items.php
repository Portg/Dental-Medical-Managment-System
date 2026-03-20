<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // stock_in_status
        foreach ([
            ['draft',     '草稿',   1],
            ['confirmed', '已确认', 2],
            ['cancelled', '已取消', 3],
        ] as $item) {
            $rows[] = ['type' => 'stock_in_status', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        // inventory_category_type
        foreach ([
            ['drug',           '药品',     1],
            ['consumable',     '耗材',     2],
            ['instrument',     '器械',     3],
            ['dental_material','牙科材料', 4],
            ['office',         '办公用品', 5],
        ] as $item) {
            $rows[] = ['type' => 'inventory_category_type', 'code' => $item[0], 'name' => $item[1], 'sort_order' => $item[2], 'created_at' => $now, 'updated_at' => $now];
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('dict_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('dict_items')->whereIn('type', [
            'stock_in_status',
            'inventory_category_type',
        ])->delete();
    }
};
