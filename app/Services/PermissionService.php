<?php

namespace App\Services;

use App\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionService
{
    /**
     * Get all permissions for listing.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    /**
     * Find a permission by ID.
     */
    public function findPermission(int $id): ?Permission
    {
        return Permission::where('id', $id)->first();
    }

    /**
     * Create a new permission.
     */
    public function createPermission(string $name, ?string $slug, ?string $description, ?string $module): ?Permission
    {
        return Permission::create([
            'name' => $name,
            'slug' => $slug ?: Str::slug($name),
            'description' => $description,
            'module' => $module,
        ]);
    }

    /**
     * Update an existing permission.
     */
    public function updatePermission(int $id, string $name, string $slug, ?string $description, ?string $module): bool
    {
        return (bool) Permission::where('id', $id)->update([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'module' => $module,
        ]);
    }

    /**
     * Delete a permission.
     */
    public function deletePermission(int $id): bool
    {
        return (bool) Permission::where('id', $id)->delete();
    }
}
