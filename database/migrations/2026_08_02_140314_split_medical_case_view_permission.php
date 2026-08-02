<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 病历读写分权，收回护士的完整病历管理权限。
 *
 * 2026_08_01_000002 为了让护士能录护理记录，直接授予了 manage-medical-cases，
 * 而 MedicalCaseController 整个控制器都挂着这个权限 —— 等于连带给了建档、改写、
 * 删除、修改审批、PDF 归档的能力，明显越权。
 *
 * 现拆为：
 *   - view-medical-cases   打开病历、查看修改记录与版本历史、打印导出
 *   - manage-medical-cases 建档、改写、删除、审批、归档（保持原语义）
 *
 * 护士改为只持有 view-medical-cases；录入生命体征与护理记录本就走
 * VitalSignController / ProgressNoteController 的 edit-patients，护士已持有，
 * 因此本次收权不影响其日常工作。
 */
return new class extends Migration
{
    private const VIEW_ROLES = ['super-admin', 'admin', 'doctor', 'nurse'];

    public function up(): void
    {
        $viewId = DB::table('permissions')->where('slug', 'view-medical-cases')->value('id');

        if (!$viewId) {
            $viewId = DB::table('permissions')->insertGetId([
                'name'        => '查看病历',
                'slug'        => 'view-medical-cases',
                'module'      => '病历管理',
                'description' => '打开病历、查看修改记录与版本历史、打印导出，不含建档与改写',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 原先能进病历的角色都要拿到 view，否则本次拆分会把他们挡在门外
        foreach (DB::table('roles')->whereIn('slug', self::VIEW_ROLES)->pluck('id') as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $viewId,
            ]);
            Cache::forget("role:{$roleId}:permissions");
        }

        // 收回护士的 manage-medical-cases
        $nurseId  = DB::table('roles')->where('slug', 'nurse')->value('id');
        $manageId = DB::table('permissions')->where('slug', 'manage-medical-cases')->value('id');

        if ($nurseId && $manageId) {
            DB::table('role_permissions')
                ->where('role_id', $nurseId)
                ->where('permission_id', $manageId)
                ->delete();
            Cache::forget("role:{$nurseId}:permissions");
        }
    }

    public function down(): void
    {
        $nurseId  = DB::table('roles')->where('slug', 'nurse')->value('id');
        $manageId = DB::table('permissions')->where('slug', 'manage-medical-cases')->value('id');

        if ($nurseId && $manageId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $nurseId,
                'permission_id' => $manageId,
            ]);
            Cache::forget("role:{$nurseId}:permissions");
        }

        // 不删 view-medical-cases，也不删它的角色授权。
        //
        // up() 对权限用「有则复用、无则新建」，对授权用 insertOrIgnore —— 迁移跑完
        // 就无从分辨哪些是自己新增的、哪些是客户环境本来就有的。无条件删除会连带
        // 撤销迁移前就存在的配置。同 2026_08_01_000002 的处理（见 c9c611e）。
        //
        // 另外 MedicalCaseController 的只读动作已经改挂 view-medical-cases，
        // 删掉这个权限会让回滚后的只读入口直接 403。

        foreach (DB::table('roles')->pluck('id') as $roleId) {
            Cache::forget("role:{$roleId}:permissions");
        }
    }
};
