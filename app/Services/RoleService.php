<?php

namespace App\Services;

use App\Role;
use Illuminate\Support\Collection;

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
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name];
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
}
