<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientTagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get a user ID for _who_added
        $userId = DB::table('users')->first()->id ?? 1;

        // Create patient tags
        $tags = [
            ['name' => 'VIP客户', 'color' => '#FFD700', 'icon' => 'fa fa-star', 'sort_order' => 1],
            ['name' => '过敏体质', 'color' => '#FF4444', 'icon' => 'fa fa-exclamation-triangle', 'sort_order' => 2],
            ['name' => '儿童', 'color' => '#4FC3F7', 'icon' => 'fa fa-child', 'sort_order' => 3],
            ['name' => '老人', 'color' => '#9C27B0', 'icon' => 'fa fa-user', 'sort_order' => 4],
            ['name' => '孕妇', 'color' => '#FF69B4', 'icon' => 'fa fa-heart', 'sort_order' => 5],
            ['name' => '特殊需求', 'color' => '#FF9800', 'icon' => 'fa fa-wheelchair', 'sort_order' => 6],
            ['name' => '正畸患者', 'color' => '#00BCD4', 'icon' => 'fa fa-magic', 'sort_order' => 7],
            ['name' => '种植患者', 'color' => '#4CAF50', 'icon' => 'fa fa-plus-circle', 'sort_order' => 8],
        ];

        foreach ($tags as $tag) {
            DB::table('patient_tags')->insert([
                'name' => $tag['name'],
                'color' => $tag['color'],
                'icon' => $tag['icon'],
                'sort_order' => $tag['sort_order'],
                'is_active' => true,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create patient sources
        $sources = [
            ['name' => '美团', 'code' => 'meituan'],
            ['name' => '大众点评', 'code' => 'dianping'],
            ['name' => '朋友推荐', 'code' => 'friend_referral'],
            ['name' => '路过', 'code' => 'walk_in'],
            ['name' => '网站预约', 'code' => 'website'],
            ['name' => '电话咨询', 'code' => 'phone'],
            ['name' => '微信公众号', 'code' => 'wechat'],
            ['name' => '抖音', 'code' => 'douyin'],
            ['name' => '小红书', 'code' => 'xiaohongshu'],
            ['name' => '其他', 'code' => 'other'],
        ];

        foreach ($sources as $source) {
            DB::table('patient_sources')->insert([
                'name' => $source['name'],
                'code' => $source['code'],
                'is_active' => true,
                '_who_added' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
