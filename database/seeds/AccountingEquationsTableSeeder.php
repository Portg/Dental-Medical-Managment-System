<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingEquationsTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充会计方程式数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('accounting_equations')->truncate();

        // 插入基本会计方程式
        $equations = [
            [
                'id' => 1,
                'name' => '资产',
                'equation' => '负债 + 所有者权益',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => '所有者权益',
                'equation' => '资产 - 负债',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => '负债',
                'equation' => '资产 - 所有者权益',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => '利润',
                'equation' => '收入 - 费用',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('accounting_equations')->insert($equations);

        $this->command->info('✓ 已创建 4 条会计方程式');
    }
}
