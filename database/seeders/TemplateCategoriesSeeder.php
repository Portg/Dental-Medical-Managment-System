<?php

namespace Database\Seeders;

use App\TemplateCategory;
use Illuminate\Database\Seeder;

/**
 * 病历模板的学科分类初始树。
 *
 * 一级取自视频里的七个学科；二级是各学科下最常写模板的病种，给诊所一个能直接
 * 用的起点，而不是一棵空树 —— 空树的结果通常是没人去建，模板继续堆在一处。
 *
 * 幂等：按「同一父节点下的同名节点」upsert，重复跑不会长出第二棵树，也不会
 * 覆盖诊所自己改过的排序与启用状态。
 */
class TemplateCategoriesSeeder extends Seeder
{
    /** 一级学科 => 二级病种 */
    private const TREE = [
        '牙体牙髓病学' => ['龋病', '牙髓病', '根尖周病', '牙体缺损'],
        '牙周病学'     => ['牙龈炎', '牙周炎', '牙周维护'],
        '口腔修复学'   => ['固定修复', '活动修复', '全口义齿', '贴面美学修复'],
        '口腔正畸学'   => ['错𬌗畸形', '固定矫治', '隐形矫治', '早期矫治'],
        '口腔种植学'   => ['种植一期', '种植二期', '种植修复'],
        '颌面外科学'   => ['拔牙', '阻生牙', '颌面外伤', '囊肿与肿物'],
        '颞颌关节病'   => ['关节紊乱', '关节弹响'],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::TREE as $rootName => $children) {
            $root = TemplateCategory::firstOrCreate(
                ['name' => $rootName, 'parent_id' => null],
                ['sort_order' => $order += 10, 'is_active' => true]
            );

            $childOrder = 0;
            foreach ($children as $childName) {
                TemplateCategory::firstOrCreate(
                    ['name' => $childName, 'parent_id' => $root->id],
                    ['sort_order' => $childOrder += 10, 'is_active' => true]
                );
            }
        }
    }
}
