<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Role;

class RolesTableSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $roles = [
            ['name' => '超级管理员', 'slug' => 'super-admin'],
            ['name' => '管理员',     'slug' => 'admin'],
            ['name' => '医生',       'slug' => 'doctor'],
            ['name' => '护士',       'slug' => 'nurse'],
            ['name' => '前台',       'slug' => 'receptionist'],
        ];
        // 创建角色
        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}