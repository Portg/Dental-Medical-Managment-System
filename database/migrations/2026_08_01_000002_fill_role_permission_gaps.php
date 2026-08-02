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
            // 护士需打开病历录护理记录。2026_08_02_140314 已把只读能力拆成
            // view-medical-cases 并收回护士的 manage-medical-cases；
            // 这里保持原样是为了不改写历史迁移的行为，后续迁移会纠正。
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
        // 故意不做逆向删除。
        //
        // up() 用的是 insertOrIgnore：GRANTS 里的配对可能本来就存在（迁移只是补齐
        // 缺口），迁移结束后已无从分辨哪些是自己新增的。无条件按 GRANTS 删除会连带
        // 撤销迁移前就有的授权，把回滚变成一次权限事故——而权限被悄悄收回在医疗系统
        // 里表现为"某角色突然打不开某功能"，排查成本远高于多留几条授权。
        //
        // 真要撤销请针对具体角色手工处理，或写一条只删指定配对的新迁移。
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
