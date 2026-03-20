<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 修复三个菜单项的父节点：
 * 申领管理 / 盘点管理 / 批量导入 错误挂在 menu.group_inventory 下，
 * 统一修正为 menu.group_consumables（耗材管理），与入库/出库保持一致。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 找到正确的父节点（耗材管理）
        $correctParent = DB::table('menu_items')
            ->where('title_key', 'menu.group_consumables')
            ->first();

        if (!$correctParent) {
            // 父节点不存在，无法修复，跳过
            return;
        }

        $parentId = $correctParent->id;

        // 修复：申领管理（menu.requisition_management）
        DB::table('menu_items')
            ->where('title_key', 'menu.requisition_management')
            ->update(['parent_id' => $parentId]);

        // 修复：盘点管理（menu.inventory_check_management）
        DB::table('menu_items')
            ->where('title_key', 'menu.inventory_check_management')
            ->update(['parent_id' => $parentId]);

        // 修复：批量导入（url = inventory-import）
        DB::table('menu_items')
            ->where('url', 'inventory-import')
            ->update(['parent_id' => $parentId]);

        // 如果 group_inventory 父节点是多余的空壳，顺带清理（无子节点时删除）
        $orphanGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_inventory')
            ->first();

        if ($orphanGroup) {
            $hasChildren = DB::table('menu_items')
                ->where('parent_id', $orphanGroup->id)
                ->exists();

            if (!$hasChildren) {
                DB::table('role_menu_items')->where('menu_item_id', $orphanGroup->id)->delete();
                DB::table('menu_items')->where('id', $orphanGroup->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // 不可逆操作（原父节点可能已被清理），down 留空
    }
};
