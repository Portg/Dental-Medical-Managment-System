<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充休假类型数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('leave_types')->truncate();

        // 插入休假类型
        $leaveTypes = [
            ['id' => 1, 'name' => '年假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '病假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '产假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '陪产假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => '事假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => '调休', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => '婚假', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => '丧假', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('leave_types')->insert($leaveTypes);

        $this->command->info('✓ 已创建 8 种休假类型');
    }
}
