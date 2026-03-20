<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 添加「批量导入」菜单项到库存管理分组。
 * 权限：manage-inventory（库管/管理员）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 幂等检查
        if (DB::table('menu_items')->where('url', 'inventory-import')->exists()) {
            Cache::forget('menu_tree:all');
            return;
        }

        // 确保 manage-inventory 权限存在
        $permId = DB::table('permissions')->where('slug', 'manage-inventory')->value('id');
        if (!$permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name'        => 'Manage Inventory',
                'slug'        => 'manage-inventory',
                'description' => '管理库存（管理员/库管）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 查找库存管理父节点（group_inventory 或 group_consumables）
        $inventoryGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_inventory')
            ->orWhere('title_key', 'menu.group_consumables')
            ->orderByDesc('id')
            ->first();

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $inventoryGroup ? $inventoryGroup->id : null,
            'title_key'     => 'inventory.bulk_import',
            'url'           => 'inventory-import',
            'icon'          => 'fa fa-file-excel-o',
            'permission_id' => $permId,
            'sort_order'    => 98,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // 绑定 SuperAdmin / Admin 角色
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_menu_items')->insertOrIgnore([
                'role_id'      => $roleId,
                'menu_item_id' => $menuItemId,
            ]);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        $item = DB::table('menu_items')->where('url', 'inventory-import')->first();
        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }
};
