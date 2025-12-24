<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充系统角色数据
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('roles')->truncate();

        // 插入基础角色
        $roles = [
            ['id' => 1, 'name' => '超级管理员', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => '医生', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => '护士', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => '前台接待', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => '药剂师', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => '会计', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => '化验师', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('roles')->insert($roles);

        $this->command->info('✓ 已创建 7 个系统角色');
    }
}
