<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsSeeder extends Seeder
{
    public function run()
    {
        $adminId = User::first()->id;
        $branchId = DB::table('branches')->whereNull('deleted_at')->value('id');
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->addMonths(3)->endOfMonth()->toDateString();

        $coupons = [
            [
                'code' => 'NEW100',
                'name' => '新客体验券',
                'description' => '首次就诊患者专享，满500减100',
                'type' => 'fixed',
                'value' => 100.00,
                'min_order_amount' => 500.00,
                'max_discount_amount' => null,
                'total_quantity' => 200,
                'used_quantity' => 0,
                'per_user_limit' => 1,
                'applicable_services' => null,
                'applicable_member_levels' => null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CLEAN20',
                'name' => '洁牙折扣券',
                'description' => '洁牙项目享8折优惠',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_amount' => 200.00,
                'max_discount_amount' => 200.00,
                'total_quantity' => 100,
                'used_quantity' => 0,
                'per_user_limit' => 1,
                'applicable_services' => null,
                'applicable_member_levels' => null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FEST200',
                'name' => '节日特惠券',
                'description' => '满1000减200，限节假日使用',
                'type' => 'fixed',
                'value' => 200.00,
                'min_order_amount' => 1000.00,
                'max_discount_amount' => null,
                'total_quantity' => 50,
                'used_quantity' => 0,
                'per_user_limit' => 1,
                'applicable_services' => null,
                'applicable_member_levels' => null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'branch_id' => $branchId,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('coupons')->insert($coupons);
        $this->command->info('✓ 已创建 3 张优惠券（示例数据，请根据实际情况调整）');
    }
}
