<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChairsTableSeeder extends Seeder
{
    public function run()
    {
        $adminId = User::first()->id;
        $branchId = DB::table('branches')->whereNull('deleted_at')->value('id');

        $chairs = [
            ['chair_code' => 'CH-01', 'chair_name' => '1号椅', 'status' => 'active', 'branch_id' => $branchId, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['chair_code' => 'CH-02', 'chair_name' => '2号椅', 'status' => 'active', 'branch_id' => $branchId, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['chair_code' => 'CH-03', 'chair_name' => '3号椅', 'status' => 'active', 'branch_id' => $branchId, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('chairs')->insert($chairs);
        $this->command->info('✓ 已创建 3 个诊室椅位');
    }
}
