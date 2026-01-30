<?php

use App\Branch;
use App\Role;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 获取管理员角色的ID
        $adminRole = Role::where('name', 'Super Administrator')->first();
        $mainBranch = Branch::where('name', '瑞贝口腔')->whereNull('deleted_at')  ->first();

        // 创建一个管理员账号
        User::create([
            'surname' => 'Admin',
            'othername' => 'User',
            'email' => 'admin@example.com',
            'phone_no' => '1234567890',
            'password' => Hash::make('password'),
            'is_doctor' => 'No',
            'role_id' => $adminRole->id,
            'branch_id' => $mainBranch->id
        ]);
    }
}