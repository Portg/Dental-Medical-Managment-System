<?php

namespace App\Services;

use App\Permission;
use App\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Get all roles for listing.
     */
    public function getAllRoles(): Collection
    {
        return Role::all();
    }

    /**
     * Search roles by name.
     */
    public function searchRoles(string $keyword): array
    {
        $data = Role::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name, 'slug' => $tag->slug];
        }

        return $formatted;
    }

    /**
     * Find a role by ID.
     */
    public function findRole(int $id): ?Role
    {
        return Role::where('id', $id)->first();
    }

    /**
     * Create a new role.
     */
    public function createRole(string $name): ?Role
    {
        return Role::create(['name' => $name]);
    }

    /**
     * Update an existing role.
     */
    public function updateRole(int $id, string $name): bool
    {
        return (bool) Role::where('id', $id)->update(['name' => $name]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): bool
    {
        return (bool) Role::where('id', $id)->delete();
    }

    /**
     * 带用户数的角色查询（详情页用）。
     */
    public function findRoleWithDetails(int $id): ?Role
    {
        return Role::withCount('users')->find($id);
    }

    /**
     * 批量同步角色权限。
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $role = Role::find($roleId);
        if (!$role) {
            return false;
        }

        $role->permissions()->sync($permissionIds);
        Cache::forget("role:{$roleId}:permissions");

        return true;
    }

    /**
     * 获取所有权限，按 module 分组。
     */
    public function getPermissionsGroupedByModule(): Collection
    {
        return Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
    }

    /**
     * 获取角色已有的权限 ID 列表。
     */
    public function getRolePermissionIds(int $roleId): array
    {
        return DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->toArray();
    }

    // ── 角色模板 ─────────────────────────────────────────────

    /**
     * 预定义的角色模板及其权限 slug 列表。
     */
    private const TEMPLATES = [
        'admin' => [
            'view-patients', 'create-patients', 'edit-patients', 'delete-patients',
            'view-appointments', 'create-appointments', 'edit-appointments', 'delete-appointments',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
            'view-users', 'create-users', 'edit-users',
            'view-branches', 'create-branches', 'edit-branches',
            'view-chairs', 'create-chairs', 'edit-chairs', 'delete-chairs',
            'view-reports', 'export-reports', 'export-patients', 'view-sensitive-data',
            'manage-medical-cases', 'manage-treatments', 'manage-medical-services',
            'manage-quotations', 'manage-refunds', 'manage-doctor-claims', 'manage-expenses',
            'manage-accounting', 'manage-inventory', 'manage-labs',
            'manage-payroll', 'manage-leave', 'manage-employees', 'manage-holidays',
            'manage-schedules', 'manage-insurance', 'manage-members',
            'manage-patient-settings', 'manage-sms', 'manage-settings',
            'manage-system-maintenance',
        ],
        'doctor' => [
            'view-patients', 'edit-patients',
            'view-appointments', 'edit-appointments',
            'view-invoices',
            'manage-medical-cases', 'manage-treatments',
        ],
        'nurse' => [
            'view-patients', 'edit-patients',
            'view-appointments',
        ],
        'receptionist' => [
            'view-patients', 'create-patients', 'edit-patients',
            'view-appointments', 'create-appointments', 'edit-appointments',
            'view-invoices', 'create-invoices',
            'manage-quotations', 'manage-schedules',
        ],
    ];

    /**
     * 获取可用模板列表（slug → 翻译名称）。
     */
    public function getTemplates(): array
    {
        $list = [];
        foreach (array_keys(self::TEMPLATES) as $slug) {
            $list[] = [
                'slug'  => $slug,
                'label' => __("roles.template_{$slug}"),
                'count' => count(self::TEMPLATES[$slug]),
            ];
        }
        return $list;
    }

    /**
     * 获取指定模板的权限 ID 列表。
     */
    public function getTemplatePermissionIds(string $templateSlug): ?array
    {
        $slugs = self::TEMPLATES[$templateSlug] ?? null;
        if ($slugs === null) {
            return null;
        }

        return Permission::whereIn('slug', $slugs)->pluck('id')->toArray();
    }
}
