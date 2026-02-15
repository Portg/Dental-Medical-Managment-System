<?php

namespace App\Services;

use App\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchService
{
    private const CACHE_KEY_ALL = 'branches:all';
    private const CACHE_TTL = 21600; // 6h

    /**
     * Get all branches with who-added user info for listing.
     */
    public function getBranchList(): Collection
    {
        return DB::table('branches')
            ->leftJoin('users', 'users.id', 'branches._who_added')
            ->whereNull('branches.deleted_at')
            ->select(['branches.*', 'users.surname'])
            ->orderBy('branches.id', 'desc')
            ->get();
    }

    /**
     * Get all branches (cached, for dropdowns).
     */
    public function getAllBranches(): Collection
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL, function () {
            return Branch::whereNull('deleted_at')->orderBy('name')->get();
        });
    }

    /**
     * Search branches by name.
     */
    public function searchBranches(string $keyword): array
    {
        $data = Branch::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name];
        }

        return $formatted;
    }

    /**
     * Find a branch by ID.
     */
    public function findBranch(int $id): ?Branch
    {
        return Branch::where('id', $id)->first();
    }

    /**
     * Create a new branch.
     */
    public function createBranch(string $name, int $userId): ?Branch
    {
        $branch = Branch::create(['name' => $name, '_who_added' => $userId]);

        Cache::forget(self::CACHE_KEY_ALL);

        return $branch;
    }

    /**
     * Update an existing branch.
     */
    public function updateBranch(int $id, string $name, int $userId): bool
    {
        $result = (bool) Branch::where('id', $id)->update(['name' => $name, '_who_added' => $userId]);

        Cache::forget(self::CACHE_KEY_ALL);

        return $result;
    }

    /**
     * Delete a branch (soft-delete).
     */
    public function deleteBranch(int $id): bool
    {
        $result = (bool) Branch::where('id', $id)->delete();

        Cache::forget(self::CACHE_KEY_ALL);

        return $result;
    }
}
