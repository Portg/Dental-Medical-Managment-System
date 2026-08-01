<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 补齐角色权限缺口。
 *
 * 来源于「MenuItemsSeeder 中表达的角色意图」与「role_permissions 实际分配」的比对：
 * 规划给了该角色、但角色并未持有对应权限，导致功能虽已实现却无人可用。
 */
return new class extends Migration
{
    /** 角色 slug => 需补发的权限 slug */
    private const GRANTS = [
        'doctor' => [
            // DoctorScheduleController 的闭包中间件放行 view-own-schedule，
            // DoctorScheduleService 亦为其准备了「只看自己」的降级视图，
            // 但该权限此前仅超管持有 → 医生查不到自己的出诊排班。
            'view-own-schedule',
            // 医生制定治疗方案后需要出报价单
            'manage-quotations',
        ],
        'receptionist' => [
            // 前台是办卡、储值、核销优惠券的第一线
            'manage-members',
            // 前台负责日常杂费/报销录入
            'manage-expenses',
        ],
        'nurse' => [
            // 护士需录入护理记录（注意：该权限同时包含病历完整读写，暂无更细粒度）
            'manage-medical-cases',
        ],
    ];

    public function up(): void
    {
        $this->apply(fn ($roleId, $permId) => DB::table('role_permissions')->insertOrIgnore([
            'role_id'       => $roleId,
            'permission_id' => $permId,
        ]));
    }

    public function down(): void
    {
        $this->apply(fn ($roleId, $permId) => DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permId)
            ->delete());
    }

    private function apply(callable $op): void
    {
        $roleIds = DB::table('roles')->pluck('id', 'slug');
        $permIds = DB::table('permissions')->pluck('id', 'slug');

        foreach (self::GRANTS as $roleSlug => $permSlugs) {
            $roleId = $roleIds[$roleSlug] ?? null;
            if ($roleId === null) {
                continue;
            }

            foreach ($permSlugs as $permSlug) {
                $permId = $permIds[$permSlug] ?? null;
                if ($permId === null) {
                    continue;
                }
                $op($roleId, $permId);
            }

            // Role::hasPermission() 按角色缓存权限 slug 列表，必须失效
            Cache::forget("role:{$roleId}:permissions");
        }

        Cache::forget('menu_tree:all');
    }
};
