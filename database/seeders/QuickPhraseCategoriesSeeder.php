<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuickPhraseCategoriesSeeder extends Seeder
{
    public function run()
    {
        $adminId = User::first()->id;

        $categories = [
            ['name' => '检查',   'description' => '临床检查结果相关短语', 'display_order' => 1, 'is_active' => true, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '诊断',   'description' => '诊断结论相关短语',     'display_order' => 2, 'is_active' => true, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '治疗',   'description' => '治疗操作相关短语',     'display_order' => 3, 'is_active' => true, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '医嘱',   'description' => '术后医嘱和注意事项',   'display_order' => 4, 'is_active' => true, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '主诉',   'description' => '患者主诉描述短语',     'display_order' => 5, 'is_active' => true, '_who_added' => $adminId, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('quick_phrase_categories')->insert($categories);
        $this->command->info('✓ 已创建 5 个快捷短语分类');
    }
}
