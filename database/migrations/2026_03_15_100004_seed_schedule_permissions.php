<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add manage-shifts permission
        $exists = DB::table('permissions')->where('slug', 'manage-shifts')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'name'        => '管理班次',
                'slug'        => 'manage-shifts',
                'module'      => '人事管理',
                'description' => '管理排班班次模板（上午班/下午班/休息等）',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Add view-own-schedule, edit-own-schedule, view-all-schedules permissions
        $newPermissions = [
            ['name' => '查看自己排班', 'slug' => 'view-own-schedule', 'module' => '人事管理', 'description' => '查看自己的排班'],
            ['name' => '编辑自己排班', 'slug' => 'edit-own-schedule', 'module' => '人事管理', 'description' => '编辑自己的排班（仅未来日期）'],
            ['name' => '查看所有排班', 'slug' => 'view-all-schedules', 'module' => '人事管理', 'description' => '查看所有医护人员排班（只读）'],
        ];

        foreach ($newPermissions as $perm) {
            $exists = DB::table('permissions')->where('slug', $perm['slug'])->exists();
            if (!$exists) {
                DB::table('permissions')->insert(array_merge($perm, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // Assign manage-shifts to roles that already have manage-schedules
        $manageSchedulesPerm = DB::table('permissions')->where('slug', 'manage-schedules')->first();
        $manageShiftsPerm = DB::table('permissions')->where('slug', 'manage-shifts')->first();

        if ($manageSchedulesPerm && $manageShiftsPerm) {
            $roleIds = DB::table('role_permissions')
                ->where('permission_id', $manageSchedulesPerm->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $manageShiftsPerm->id)
                    ->exists();

                if (!$exists) {
                    DB::table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $manageShiftsPerm->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = ['manage-shifts', 'view-own-schedule', 'edit-own-schedule', 'view-all-schedules'];
        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
};
