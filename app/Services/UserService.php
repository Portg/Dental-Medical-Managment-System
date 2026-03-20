<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\OperationLog;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Overtrue\Pinyin\Pinyin;

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
     * Search doctors by keyword (AG-029: only active doctors).
     */
    public function searchDoctors(?string $keyword): array
    {
        $query = DB::table('users')
            ->whereNull('users.deleted_at')
            ->where('users.is_doctor', true)
            ->where('users.status', User::STATUS_ACTIVE);

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
     * Search employees by keyword (only active).
     */
    public function searchEmployees(string $keyword): array
    {
        $data = DB::table('users')
            ->whereNull('users.deleted_at')
            ->where('users.status', User::STATUS_ACTIVE)
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
        $username = $data['username'] ?? $this->generateUsername($nameParts['surname'] . $nameParts['othername']);

        return User::create([
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'username' => $username,
            'email' => $data['email'],
            'phone_no' => $data['phone_no'] ?? null,
            'alternative_no' => $data['alternative_no'] ?? null,
            'nin' => $data['nin'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'is_doctor' => $data['is_doctor'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => User::STATUS_ACTIVE,
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
        $updateData = [
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'email' => $data['email'],
            'phone_no' => $data['phone_no'] ?? null,
            'alternative_no' => $data['alternative_no'] ?? null,
            'role_id' => $data['role_id'] ?? null,
            'is_doctor' => $data['is_doctor'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'nin' => $data['nin'] ?? null,
        ];

        if (!empty($data['username'])) {
            $updateData['username'] = $data['username'];
        }

        return (bool) User::where('id', $id)->update($updateData);
    }

    /**
     * Change user status (AG-027/AG-031).
     *
     * @param string $newPassword Required when reactivating (AG-031)
     */
    public function changeUserStatus(int $id, string $status, ?string $newPassword = null): bool
    {
        $user = User::findOrFail($id);
        $oldStatus = $user->status;

        if ($status === User::STATUS_RESIGNED) {
            $user->markAsResigned();
            OperationLog::logUpdate('users', 'User', (string) $id,
                ['status' => $oldStatus], ['status' => $status]);
            return true;
        }

        if ($status === User::STATUS_ACTIVE && $oldStatus === User::STATUS_RESIGNED) {
            // AG-031: 从离职恢复必须重置密码
            if (empty($newPassword)) {
                return false;
            }
            $user->update(['password' => Hash::make($newPassword)]);
            $user->markAsActive();
            OperationLog::logUpdate('users', 'User', (string) $id,
                ['status' => $oldStatus], ['status' => $status]);
            return true;
        }

        return false;
    }

    /**
     * Generate unique username from name (AG-028).
     * Chinese names: pinyin abbreviation (e.g. 关立亚 → gly)
     * English names: lowercase letters only (e.g. Admin User → adminuser)
     */
    public function generateUsername(string $fullName): string
    {
        $fullName = trim($fullName);
        $base = $this->nameToAbbr($fullName);

        if (empty($base)) {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;
        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    private function nameToAbbr(string $name): string
    {
        // 检测是否含中文字符
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $name)) {
            $pinyin = new Pinyin();
            // v6: abbr() 默认分隔符是空格，需要去掉
            $abbr = $pinyin->abbr($name, '');
            return strtolower(str_replace(' ', '', $abbr));
        }

        // 英文：直接用小写字母（去除非字母字符）
        return strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
    }

    /**
     * Delete a user (soft-delete).
     */
    public function deleteUser(int $id): bool
    {
        return (bool) User::where('id', $id)->delete();
    }
}
