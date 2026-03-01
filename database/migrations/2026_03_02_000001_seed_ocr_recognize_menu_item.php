<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find the parent "患者档案" group menu item
        $parent = DB::table('menu_items')
            ->where('title_key', 'menu.group_patient_management')
            ->first();

        if (!$parent) {
            return;
        }

        // Check if already exists (idempotent)
        $exists = DB::table('menu_items')
            ->where('title_key', 'menu.ocr_recognize')
            ->exists();

        if ($exists) {
            return;
        }

        // Find the permission: create-patients
        $permId = DB::table('permissions')
            ->where('slug', 'create-patients')
            ->value('id');

        // Insert menu item (sort_order 15 = between patients_list(10) and patient_tags(20))
        $menuItemId = DB::table('menu_items')->insertGetId([
            'parent_id'     => $parent->id,
            'title_key'     => 'menu.ocr_recognize',
            'url'           => 'ocr-recognize',
            'icon'          => null,
            'permission_id' => $permId,
            'sort_order'    => 15,
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Associate with Super Admin, Admin, Receptionist roles
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['super-admin', 'admin', 'receptionist'])
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
            ->where('title_key', 'menu.ocr_recognize')
            ->first();

        if ($menuItem) {
            DB::table('role_menu_items')->where('menu_item_id', $menuItem->id)->delete();
            DB::table('menu_items')->where('id', $menuItem->id)->delete();
            Cache::forget('menu_tree:all');
        }
    }
};
