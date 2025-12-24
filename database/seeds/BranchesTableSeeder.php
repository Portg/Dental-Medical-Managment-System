<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充分支机构数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('branches')->truncate();

        // 插入分支机构
        $branches = [
            [
                'id' => 1,
                'name' => '总院',
                'is_active' => 'true',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => '分院一部',
                'is_active' => 'true',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => '分院二部',
                'is_active' => 'true',
                '_who_added' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('branches')->insert($branches);

        // 更新用户的分支信息（将所有默认用户分配到总院）
        DB::table('users')
            ->whereIn('id', [1, 2, 3, 4])
            ->update(['branch_id' => 1]);

        $this->command->info('✓ 已创建 3 个分支机构');
        $this->command->info('✓ 已将默认用户分配到总院');
    }
}
