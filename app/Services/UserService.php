<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get filtered user list for DataTables.
     */
    public function getUserList(array $filters): Collection
    {
        $query = DB::table('users')
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->leftJoin('branches', 'branches.id', 'users.branch_id')
            ->whereNull('users.deleted_at')
            ->select(['users.*', 'roles.name as user_role', 'branches.name as branch']);

        $search = $filters['search'] ?? '';
        if (is_array($search)) {
            $search = $search['value'] ?? '';
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'users');
                $q->orWhere('users.email', 'like', '%' . $search . '%')
                  ->orWhere('users.phone_no', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('users.id', 'desc')->get();
    }

    /**
     * Search doctors by keyword.
     */
    public function searchDoctors(?string $keyword): array
    {
        $query = DB::table('users')
            ->whereNull('users.deleted_at')
            ->where('users.is_doctor', true);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                NameHelper::addNameSearch($q, $keyword, 'users');
            });
        }

        $data = $query->orderBy('surname')->limit(20)->get();

        $formatted = [];
        foreach ($data as $item) {
            $formatted[] = ['id' => $item->id, 'text' => NameHelper::join($item->surname, $item->othername)];
        }

        return $formatted;
    }

    /**
     * Search employees by keyword.
     */
    public function searchEmployees(string $keyword): array
    {
        $data = DB::table('users')
            ->whereNull('users.deleted_at')
            ->where(function ($q) use ($keyword) {
                NameHelper::addNameSearch($q, $keyword);
            })
            ->select('users.*')
            ->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => NameHelper::join($tag->surname, $tag->othername)];
        }

        return $formatted;
    }

    /**
     * Parse name parts from input (locale-adaptive).
     *
     * @return array{surname: string, othername: string}
     */
    public function parseNameParts(array $input): array
    {
        if (!empty($input['full_name'])) {
            return NameHelper::split($input['full_name']);
        }

        return ['surname' => $input['surname'] ?? '', 'othername' => $input['othername'] ?? ''];
    }

    /**
     * Create a new user.
     */
    public function createUser(array $nameParts, array $data): ?User
    {
        return User::create([
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'email' => $data['email'],
            'phone_no' => $data['phone_no'] ?? null,
            'alternative_no' => $data['alternative_no'] ?? null,
            'nin' => $data['nin'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'is_doctor' => $data['is_doctor'] ?? null,
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Get user data for editing.
     */
    public function getUserForEdit(int $id): ?object
    {
        return DB::table('users')
            ->leftJoin('branches', 'branches.id', 'users.branch_id')
            ->leftJoin('roles', 'roles.id', 'users.role_id')
            ->where('users.id', $id)
            ->whereNull('users.deleted_at')
            ->select('users.*', 'roles.name as user_role', 'branches.name as branch')
            ->first();
    }

    /**
     * Update an existing user.
     */
    public function updateUser(int $id, array $nameParts, array $data): bool
    {
        return (bool) User::where('id', $id)->update([
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'email' => $data['email'],
            'phone_no' => $data['phone_no'] ?? null,
            'alternative_no' => $data['alternative_no'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'is_doctor' => $data['is_doctor'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'nin' => $data['nin'] ?? null,
        ]);
    }

    /**
     * Delete a user (soft-delete).
     */
    public function deleteUser(int $id): bool
    {
        return (bool) User::where('id', $id)->delete();
    }
}
