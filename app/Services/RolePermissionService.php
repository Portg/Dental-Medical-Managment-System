<?php

namespace App\Services;

use App\Permission;
use App\Role;
use App\RolePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RolePermissionService
{
    /**
     * Get all role-permissions with eager-loaded relationships.
     */
    public function getAllRolePermissions(): Collection
    {
        return RolePermission::with(['role', 'permission'])->get();
    }

    /**
     * Get all roles.
     */
    public function getAllRoles(): Collection
    {
        return Role::all();
    }

    /**
     * Get all permissions.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    /**
     * Find a role-permission by ID with relationships.
     */
    public function findRolePermission(int $id): ?RolePermission
    {
        return RolePermission::with(['role', 'permission'])->where('id', $id)->first();
    }

    /**
     * Check if a role-permission combination already exists.
     */
    public function exists(int $roleId, int $permissionId, ?int $excludeId = null): bool
    {
        $query = RolePermission::where('role_id', $roleId)
            ->where('permission_id', $permissionId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Create a new role-permission mapping.
     */
    public function createRolePermission(int $roleId, int $permissionId): ?RolePermission
    {
        $rp = RolePermission::create([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        Cache::forget("role:{$roleId}:permissions");

        return $rp;
    }

    /**
     * Update an existing role-permission mapping.
     */
    public function updateRolePermission(int $id, int $roleId, int $permissionId): bool
    {
        $oldRp = RolePermission::find($id);
        $oldRoleId = $oldRp ? $oldRp->role_id : null;

        $result = (bool) RolePermission::where('id', $id)->update([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);

        Cache::forget("role:{$roleId}:permissions");
        if ($oldRoleId && $oldRoleId !== $roleId) {
            Cache::forget("role:{$oldRoleId}:permissions");
        }

        return $result;
    }

    /**
     * Delete a role-permission mapping.
     */
    public function deleteRolePermission(int $id): bool
    {
        $rp = RolePermission::find($id);
        $roleId = $rp ? $rp->role_id : null;

        $result = (bool) RolePermission::where('id', $id)->delete();

        if ($roleId) {
            Cache::forget("role:{$roleId}:permissions");
        }

        return $result;
    }
}
