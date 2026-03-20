<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find the parent inventory management group (menu.group_consumables, id=46)
        $parent = DB::table('menu_items')
            ->where('title_key', 'menu.group_consumables')
            ->first();

        if (!$parent) {
            return;
        }

        // Idempotent check
        $exists = DB::table('menu_items')
            ->where('url', 'inventory-query')
            ->exists();

        if ($exists) {
            return;
        }

        // Use manage-inventory permission for this menu item
        $permId = DB::table('permissions')
            ->where('slug', 'manage-inventory')
            ->value('id');

        // Insert after inventory-dashboard (which has sort_order=5 if seeded, else before stock_in at sort_order=10)
        // We use sort_order=8 to place between dashboard and stock_in
        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $parent->id,
            'title_key'     => 'inventory.inventory_query',
            'url'           => 'inventory-query',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 8,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Associate with Super Admin and Admin roles
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin'])
            ->pluck('id');

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            $pivotRows[] = [
                'role_id'      => $roleId,
                'menu_item_id' => $menuItemId,
            ];
        }

        if ($pivotRows) {
            DB::table('role_menu_items')->insert($pivotRows);
        }

        Cache::forget('menu_tree:all');
    }

    public function down(): void
    {
        $menuItem = DB::table('menu_items')
            ->where('url', 'inventory-query')
            ->first();

        if ($menuItem) {
            DB::table('role_menu_items')->where('menu_item_id', $menuItem->id)->delete();
            DB::table('menu_items')->where('id', $menuItem->id)->delete();
            Cache::forget('menu_tree:all');
        }
    }
};
