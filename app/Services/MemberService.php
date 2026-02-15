<?php

namespace App\Services;

use App\MemberLevel;
use App\MemberTransaction;
use App\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemberService
{
    private const CACHE_KEY_LEVELS = 'member_levels:all';
    private const CACHE_TTL = 86400; // 24h

    // ─── Member list ──────────────────────────────────────────────

    /**
     * Get filtered member list for DataTables.
     */
    public function getMemberList(array $filters): Collection
    {
        $query = DB::table('patients')
            ->leftJoin('member_levels', 'member_levels.id', 'patients.member_level_id')
            ->whereNull('patients.deleted_at')
            ->where('patients.member_status', '!=', 'Inactive')
            ->orderBy('patients.member_since', 'desc')
            ->select(
                'patients.*',
                'member_levels.name as level_name',
                'member_levels.color as level_color',
                'member_levels.discount_rate'
            );

        if (!empty($filters['level_id'])) {
            $query->where('patients.member_level_id', $filters['level_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('patients.member_status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * Get patients that are not yet members (for registration dropdown).
     */
    public function getNonMembers(): Collection
    {
        return Patient::whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('member_status')
                  ->orWhere('member_status', 'Inactive');
            })
            ->orderBy('surname')
            ->get();
    }

    // ─── Single member ───────────────────────────────────────────

    /**
     * Get member detail with transactions.
     */
    public function getMemberDetail(int $id): array
    {
        $patient = Patient::with(['memberLevel', 'memberTransactions' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }])->findOrFail($id);

        $levels = MemberLevel::active()->ordered()->get();

        return compact('patient', 'levels');
    }

    // ─── Member CUD ──────────────────────────────────────────────

    /**
     * Register a patient as a member.
     *
     * @return array{message: string, status: bool}
     */
    public function registerMember(array $data): array
    {
        $patient = Patient::findOrFail($data['patient_id']);

        if ($patient->member_status === 'Active') {
            return ['message' => __('members.already_member'), 'status' => false];
        }

        $patient->update([
            'member_no' => Patient::generateMemberNo(),
            'member_level_id' => $data['member_level_id'],
            'member_balance' => $data['initial_balance'] ?? 0,
            'member_points' => 0,
            'total_consumption' => 0,
            'member_since' => now(),
            'member_expiry' => $data['member_expiry'] ?? null,
            'member_status' => 'Active',
        ]);

        // Record initial deposit if any
        if (($data['initial_balance'] ?? 0) > 0) {
            MemberTransaction::create([
                'transaction_no' => MemberTransaction::generateTransactionNo(),
                'transaction_type' => 'Deposit',
                'amount' => $data['initial_balance'],
                'balance_before' => 0,
                'balance_after' => $data['initial_balance'],
                'payment_method' => $data['payment_method'] ?? 'Cash',
                'description' => __('members.initial_deposit'),
                'patient_id' => $patient->id,
                '_who_added' => Auth::user()->id,
            ]);
        }

        return ['message' => __('members.member_registered_successfully'), 'status' => true];
    }

    /**
     * Update member information.
     *
     * @return array{message: string, status: bool}
     */
    public function updateMember(int $id, array $data): array
    {
        $patient = Patient::findOrFail($id);

        $patient->update([
            'member_level_id' => $data['member_level_id'],
            'member_expiry' => $data['member_expiry'] ?? null,
            'member_status' => $data['member_status'] ?? $patient->member_status,
        ]);

        return ['message' => __('members.member_updated_successfully'), 'status' => true];
    }

    /**
     * Deposit to member balance.
     *
     * @return array{message: string, status: bool, new_balance?: float}
     */
    public function deposit(int $id, array $data): array
    {
        $patient = Patient::findOrFail($id);

        if ($patient->member_status !== 'Active') {
            return ['message' => __('members.not_active_member'), 'status' => false];
        }

        $balanceBefore = $patient->member_balance;
        $balanceAfter = $balanceBefore + $data['amount'];

        $patient->update(['member_balance' => $balanceAfter]);

        MemberTransaction::create([
            'transaction_no' => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Deposit',
            'amount' => $data['amount'],
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'payment_method' => $data['payment_method'],
            'description' => $data['description'] ?? __('members.balance_deposit'),
            'patient_id' => $patient->id,
            '_who_added' => Auth::user()->id,
        ]);

        return ['message' => __('members.deposit_successful'), 'status' => true, 'new_balance' => $balanceAfter];
    }

    // ─── Transactions ────────────────────────────────────────────

    /**
     * Get member transactions for DataTables.
     */
    public function getTransactions(int $patientId): Collection
    {
        return DB::table('member_transactions')
            ->leftJoin('users as added_by', 'added_by.id', 'member_transactions._who_added')
            ->whereNull('member_transactions.deleted_at')
            ->where('member_transactions.patient_id', $patientId)
            ->orderBy('member_transactions.created_at', 'desc')
            ->select(
                'member_transactions.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(added_by.surname, added_by.othername) as added_by_name"
                    : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
            )
            ->get();
    }

    // ─── Member Levels ───────────────────────────────────────────

    /**
     * Get all member levels for DataTables.
     */
    public function getLevelList(): Collection
    {
        return Cache::remember(self::CACHE_KEY_LEVELS, self::CACHE_TTL, function () {
            return MemberLevel::whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Get level for editing.
     */
    public function getLevel(int $id): MemberLevel
    {
        return MemberLevel::findOrFail($id);
    }

    /**
     * Create a member level.
     *
     * @return array{message: string, status: bool}
     */
    public function createLevel(array $data): array
    {
        MemberLevel::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'color' => $data['color'] ?? '#999999',
            'discount_rate' => $data['discount_rate'],
            'min_consumption' => $data['min_consumption'] ?? 0,
            'points_rate' => $data['points_rate'] ?? 1,
            'benefits' => $data['benefits'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            '_who_added' => Auth::user()->id,
        ]);

        Cache::forget(self::CACHE_KEY_LEVELS);

        return ['message' => __('members.level_created_successfully'), 'status' => true];
    }

    /**
     * Update a member level.
     *
     * @return array{message: string, status: bool}
     */
    public function updateLevel(int $id, array $data): array
    {
        MemberLevel::where('id', $id)->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'color' => $data['color'] ?? '#999999',
            'discount_rate' => $data['discount_rate'],
            'min_consumption' => $data['min_consumption'] ?? 0,
            'points_rate' => $data['points_rate'] ?? 1,
            'benefits' => $data['benefits'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_default' => $data['is_default'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        Cache::forget(self::CACHE_KEY_LEVELS);

        return ['message' => __('members.level_updated_successfully'), 'status' => true];
    }

    /**
     * Delete a member level (only if no members assigned).
     *
     * @return array{message: string, status: bool}
     */
    public function deleteLevel(int $id): array
    {
        $count = Patient::where('member_level_id', $id)->count();
        if ($count > 0) {
            return ['message' => __('members.level_has_members', ['count' => $count]), 'status' => false];
        }

        MemberLevel::where('id', $id)->delete();

        Cache::forget(self::CACHE_KEY_LEVELS);

        return ['message' => __('members.level_deleted_successfully'), 'status' => true];
    }
}
