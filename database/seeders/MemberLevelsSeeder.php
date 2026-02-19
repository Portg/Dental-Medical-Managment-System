<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberLevelsSeeder extends Seeder
{
    public function run()
    {
        $adminId = User::first()->id;

        $levels = [
            [
                'name' => '普通会员',
                'code' => 'regular',
                'color' => '#999999',
                'discount_rate' => 100.00,
                'min_consumption' => 0,
                'points_rate' => 1.00,
                'benefits' => '基础会员权益',
                'sort_order' => 1,
                'is_default' => true,
                'is_active' => true,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '银卡会员',
                'code' => 'silver',
                'color' => '#C0C0C0',
                'discount_rate' => 95.00,
                'min_consumption' => 5000.00,
                'points_rate' => 1.50,
                'benefits' => '享受95折优惠，积分1.5倍',
                'sort_order' => 2,
                'is_default' => false,
                'is_active' => true,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '金卡会员',
                'code' => 'gold',
                'color' => '#FFD700',
                'discount_rate' => 90.00,
                'min_consumption' => 20000.00,
                'points_rate' => 2.00,
                'benefits' => '享受9折优惠，积分2倍，免费洁牙1次/年',
                'sort_order' => 3,
                'is_default' => false,
                'is_active' => true,
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('member_levels')->insert($levels);
        $this->command->info('✓ 已创建 3 个会员等级');
    }
}
