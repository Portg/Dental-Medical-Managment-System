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
                'opening_fee' => 0,
                'min_initial_deposit' => 0,
                'deposit_bonus_rules' => null,
                'referral_points' => 0,
                'payment_method_points_rates' => null,
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
                'opening_fee' => 50.00,
                'min_initial_deposit' => 500.00,
                'deposit_bonus_rules' => json_encode([
                    ['min_amount' => 500, 'bonus' => 30],
                    ['min_amount' => 1000, 'bonus' => 80],
                    ['min_amount' => 3000, 'bonus' => 300],
                ]),
                'referral_points' => 50,
                'payment_method_points_rates' => json_encode([
                    'Cash' => 1.0,
                    'WeChat' => 1.5,
                    'Alipay' => 1.5,
                    'BankCard' => 1.0,
                    'StoredValue' => 0,
                ]),
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
                'opening_fee' => 100.00,
                'min_initial_deposit' => 1000.00,
                'deposit_bonus_rules' => json_encode([
                    ['min_amount' => 1000, 'bonus' => 100],
                    ['min_amount' => 3000, 'bonus' => 400],
                    ['min_amount' => 5000, 'bonus' => 800],
                ]),
                'referral_points' => 100,
                'payment_method_points_rates' => json_encode([
                    'Cash' => 1.0,
                    'WeChat' => 2.0,
                    'Alipay' => 2.0,
                    'BankCard' => 1.5,
                    'StoredValue' => 0,
                ]),
                '_who_added' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('member_levels')->insert($levels);
        $this->command->info('✓ 已创建 3 个会员等级');
    }
}
