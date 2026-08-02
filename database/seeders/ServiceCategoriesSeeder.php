<?php

namespace Database\Seeders;

use App\MedicalService;
use App\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 收费项目分类，并把项目挂到分类上。
 *
 * 2026_03_23_000002 迁移做的是「读 medical_services.category 这个旧 varchar，
 * 建出 service_categories，再回填 category_id」。逻辑本身没问题，但它是**迁移**：
 * 全新安装时 migrate 跑在 db:seed 之前，那会儿 medical_services 还是空表，
 * 于是分类建出 0 条，随后 MedicalServicesSeeder 插进来的 83 个项目
 * category_id 全是 NULL —— 收费项目的分类树整棵是空的。
 *
 * 这里把同一套逻辑放到 seeder 里、排在 MedicalServicesSeeder 之后执行，
 * 数据齐了再建分类并关联。对老库同样安全：分类按 name 幂等，
 * 只补 category_id 仍为空的项目，不动已经分好类的。
 */
class ServiceCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // 项目表里出现过的旧分类名，就是要建的分类
        $names = MedicalService::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($names->isEmpty()) {
            $this->command?->warn('ServiceCategoriesSeeder: medical_services 无分类信息，跳过（请先执行 MedicalServicesSeeder）');
            return;
        }

        $created = 0;
        foreach ($names as $name) {
            $exists = ServiceCategory::withTrashed()->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            ServiceCategory::create(['name' => $name, 'sort_order' => 0, 'is_active' => true]);
            $created++;
        }

        // 回填 category_id：只补空的，已分好类的不动
        $linked = 0;
        foreach (ServiceCategory::query()->get(['id', 'name']) as $category) {
            $linked += MedicalService::query()
                ->whereNull('category_id')
                ->where('category', $category->name)
                ->update(['category_id' => $category->id]);
        }

        $orphan = MedicalService::query()->whereNull('category_id')->count();

        $this->command?->info("收费项目分类: 新增 {$created} 个，关联 {$linked} 个项目"
            . ($orphan > 0 ? "，仍有 {$orphan} 个项目未分类" : ''));

        // 分类树有缓存，建完必须失效，否则收费页仍是旧的空树
        DB::table('medical_services')->exists() && \Illuminate\Support\Facades\Cache::forget('billing_service_category_tree');
    }
}
