<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionRulesSeeder extends Seeder
{
    public function run()
    {
        $adminId = User::first()->id;
        $branchId = DB::table('branches')->whereNull('deleted_at')->value('id');

        $rules = [
            [
                'rule_name' => '通用提成（固定比例）',
                'commission_mode' => 'fixed_percentage',
                'target_service_type' => null,
                'medical_service_id' => null,
                'base_commission_rate' => 10.00,
                'tier1_threshold' => null,
                'tier1_rate' => null,
                'tier2_threshold' => null,
                'tier2_rate' => null,
                'tier3_threshold' => null,
                'tier3_rate' => null,
                'bonus_amount' => 0,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_name' => '种植项目提成',
                'commission_mode' => 'fixed_percentage',
                'target_service_type' => '种植',
                'medical_service_id' => null,
                'base_commission_rate' => 15.00,
                'tier1_threshold' => null,
                'tier1_rate' => null,
                'tier2_threshold' => null,
                'tier2_rate' => null,
                'tier3_threshold' => null,
                'tier3_rate' => null,
                'bonus_amount' => 0,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_name' => '正畸项目阶梯提成',
                'commission_mode' => 'tiered',
                'target_service_type' => '正畸',
                'medical_service_id' => null,
                'base_commission_rate' => 10.00,
                'tier1_threshold' => 50000.00,
                'tier1_rate' => 12.00,
                'tier2_threshold' => 100000.00,
                'tier2_rate' => 15.00,
                'tier3_threshold' => 200000.00,
                'tier3_rate' => 18.00,
                'bonus_amount' => 0,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('commission_rules')->insert($rules);
        $this->command->info('✓ 已创建 3 条提成规则（示例数据，请根据实际情况调整）');
    }
}
