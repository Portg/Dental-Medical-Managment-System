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
            ['name' => 'Super Administrator'],
            ['name' => 'Administrator'],
            ['name' => 'Doctor'],
            ['name' => 'Nurse'],
            ['name' => 'Receptionist'],
        ];
        // 创建角色
        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}