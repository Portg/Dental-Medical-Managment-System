<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * 运行数据库种子
     * 填充默认用户账户
     *
     * @return void
     */
    public function run()
    {
        // 清空现有数据
        DB::table('users')->truncate();

        // 默认密码（所有账户统一使用）
        $password = Hash::make('password');

        // 插入默认用户账户
        $users = [
            // 超级管理员
            [
                'id' => 1,
                'surname' => '张',
                'othername' => '管理员',
                'email' => 'admin@dental.com',
                'phone_no' => '13800138000',
                'password' => $password,
                'role_id' => 1,
                'branch_id' => null, // 将在分支创建后更新
                'email_verified_at' => now(),
                'last_seen' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 默认医生
            [
                'id' => 2,
                'surname' => '李',
                'othername' => '医生',
                'email' => 'doctor@dental.com',
                'phone_no' => '13800138001',
                'password' => $password,
                'role_id' => 2,
                'branch_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 默认护士
            [
                'id' => 3,
                'surname' => '王',
                'othername' => '护士',
                'email' => 'nurse@dental.com',
                'phone_no' => '13800138002',
                'password' => $password,
                'role_id' => 3,
                'branch_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 默认前台
            [
                'id' => 4,
                'surname' => '赵',
                'othername' => '前台',
                'email' => 'reception@dental.com',
                'phone_no' => '13800138003',
                'password' => $password,
                'role_id' => 4,
                'branch_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);

        $this->command->info('✓ 已创建 4 个默认用户账户');
        $this->command->warn('⚠ 默认密码为: password');
        $this->command->warn('⚠ 请在首次登录后立即修改密码！');
    }
}
