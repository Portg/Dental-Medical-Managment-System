<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 添加「盘点管理」侧边栏菜单及 check_status DictItem。
 * 菜单权限绑定 manage-inventory（库管/管理员）及 operate-inventory（库房操作员）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- check_status DictItem（草稿/已确认）----
        $checkStatusItems = [
            ['code' => 'draft',     'name' => '草稿',  'sort_order' => 1],
            ['code' => 'confirmed', 'name' => '已确认', 'sort_order' => 2],
        ];
        foreach ($checkStatusItems as $item) {
            $exists = DB::table('dict_items')
                ->where('type', 'check_status')
                ->where('code', $item['code'])
                ->exists();
            if (!$exists) {
                DB::table('dict_items')->insert([
                    'type'       => 'check_status',
                    'code'       => $item['code'],
                    'name'       => $item['name'],
                    'sort_order' => $item['sort_order'],
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ---- 确保 manage-inventory 权限存在，取得其 ID ----
        $managePermId = DB::table('permissions')->where('slug', 'manage-inventory')->value('id');
        if (!$managePermId) {
            $managePermId = DB::table('permissions')->insertGetId([
                'name'        => 'Manage Inventory',
                'slug'        => 'manage-inventory',
                'description' => '管理库存（管理员/库管）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ---- 添加「盘点管理」菜单项 ----
        if (DB::table('menu_items')->where('title_key', 'menu.inventory_check_management')->exists()) {
            Cache::forget('menu_tree:all');
            return;
        }

        // 找库存管理父节点
        $inventoryGroup = DB::table('menu_items')
            ->where('title_key', 'menu.group_inventory')
            ->first();

        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $inventoryGroup ? $inventoryGroup->id : null,
            'title_key'     => 'menu.inventory_check_management',
            'url'           => 'inventory-checks',
            'icon'          => 'fa fa-check-square-o',
            'permission_id' => $managePermId,
            'sort_order'    => 95,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // 绑定 SuperAdmin / Admin 角色（manage-inventory）
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
        // check_status DictItems
        DB::table('dict_items')->where('type', 'check_status')->delete();

        // Menu
        $item = DB::table('menu_items')->where('title_key', 'menu.inventory_check_management')->first();
        if ($item) {
            DB::table('role_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }

        Cache::forget('menu_tree:all');
    }
};
