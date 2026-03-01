<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find the parent "系统管理" menu item
        $parent = DB::table('menu_items')
            ->where('title_key', 'menu.system_settings')
            ->whereNull('parent_id')
            ->first();

        if (!$parent) {
            return;
        }

        // Find the permission
        $permId = DB::table('permissions')
            ->where('slug', 'manage-settings')
            ->value('id');

        // Insert menu item
        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $parent->id,
            'title_key'     => 'menu.software_settings',
            'url'           => 'system-settings',
            'icon'          => 'icon-equalizer',
            'permission_id' => $permId,
            'sort_order'    => 38,
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
            ->where('title_key', 'menu.software_settings')
            ->first();

        if ($menuItem) {
            DB::table('role_menu_items')->where('menu_item_id', $menuItem->id)->delete();
            DB::table('menu_items')->where('id', $menuItem->id)->delete();
        }
    }
};
