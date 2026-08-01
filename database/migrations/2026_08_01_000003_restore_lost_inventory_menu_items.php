<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 恢复被 MenuItemsSeeder 抹掉的库存类菜单入口。
 *
 * 这四项由 2026_03_16_100008 / 100011 / 100013 / 100014 迁移创建，但 MenuItemsSeeder
 * 会 truncate menu_items 并按自身定义重建，而 seeder 里没有这几项 —— 只要有人执行过
 * `db:seed --class=MenuItemsSeeder`（项目开发文档中的常规步骤），它们就永久消失。
 * 路由与功能一直健在，仅仅是侧边栏没有入口，其中申领管理与盘点管理是库管的核心工作。
 *
 * seeder 已同步补入这四项，本迁移负责修复已经丢失的现有库。对未丢失的库为无操作。
 */
return new class extends Migration
{
    private const ITEMS = [
        [
            'title_key'  => 'inventory.inventory_query',
            'url'        => 'inventory-query',
            'icon'       => null,
            'permission' => 'manage-inventory',
            'sort_order' => 8,
        ],
        [
            'title_key'  => 'menu.requisition_management',
            'url'        => 'requisitions',
            'icon'       => 'fa fa-file-text-o',
            'permission' => 'request-inventory',
            'sort_order' => 90,
        ],
        [
            'title_key'  => 'menu.inventory_check_management',
            'url'        => 'inventory-checks',
            'icon'       => 'fa fa-check-square-o',
            'permission' => 'manage-inventory',
            'sort_order' => 95,
        ],
        [
            'title_key'  => 'inventory.bulk_import',
            'url'        => 'inventory-import',
            'icon'       => 'fa fa-file-excel-o',
            'permission' => 'manage-inventory',
            'sort_order' => 98,
        ],
    ];

    public function up(): void
    {
        // 父级为「耗材管理」分组（100015 迁移已将这批菜单的父级统一到此处）
        $parentId = DB::table('menu_items')
            ->where('title_key', 'menu.group_consumables')
            ->value('id');

        if ($parentId === null) {
            // 菜单结构已重组（运营中心拆分后耗材迁至库房管理）或尚未初始化，
            // 此时交由 MenuItemsSeeder 建立完整结构，本迁移不做猜测性插入。
            return;
        }

        $permIds = DB::table('permissions')->pluck('id', 'slug');
        $now     = now();

        foreach (self::ITEMS as $item) {
            if (DB::table('menu_items')->where('title_key', $item['title_key'])->exists()) {
                continue;
            }

            $permId = $permIds[$item['permission']] ?? null;
            if ($permId === null) {
                // 权限缺失时跳过，避免写出 permission_id = null 而变成「全员可见」
                continue;
            }

            DB::table('menu_items')->insert([
                'parent_id'     => $parentId,
                'title_key'     => $item['title_key'],
                'url'           => $item['url'],
                'icon'          => $item['icon'],
                'permission_id' => $permId,
                'sort_order'    => $item['sort_order'],
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        // 有意留空：up() 是「缺则补」的条件插入，无法区分某一项是本迁移补回的，
        // 还是该库本来就有的。若在此按 title_key 删除，会误删未丢失库中的正常菜单。
        // 这四项本就是迁移创建的既有功能入口，回滚时保留它们不会造成不一致。
    }
};
