<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\MemberAuditLog;
use App\MemberLevel;
use App\SystemSetting;
use App\MemberTransaction;
use App\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

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

        $level = MemberLevel::findOrFail($data['member_level_id']);
        $initialBalance = (float) ($data['initial_balance'] ?? 0);

        // Validate min initial deposit
        if ($level->min_initial_deposit > 0 && $initialBalance < $level->min_initial_deposit) {
            return [
                'message' => __('members.min_deposit_required', ['amount' => $level->min_initial_deposit]),
                'status'  => false,
            ];
        }

        // Generate member number based on configured mode
        $memberNo = Patient::generateMemberNo($patient, $data['manual_card_number'] ?? null);

        // Calculate opening fee and bonus
        $openingFee = (float) $level->opening_fee;
        $bonus = $level->calculateBonus($initialBalance);
        $creditAmount = $initialBalance - $openingFee + $bonus;

        $patient->update([
            'member_no'        => $memberNo,
            'member_level_id'  => $data['member_level_id'],
            'member_balance'   => max(0, $creditAmount),
            'member_points'    => 0,
            'total_consumption' => 0,
            'member_since'     => now(),
            'member_expiry'    => $data['member_expiry'] ?? null,
            'member_status'    => 'Active',
        ]);

        // Record initial deposit transaction if any
        if ($initialBalance > 0) {
            MemberTransaction::create([
                'transaction_no'   => MemberTransaction::generateTransactionNo(),
                'transaction_type' => 'Deposit',
                'amount'           => $initialBalance,
                'balance_before'   => 0,
                'balance_after'    => max(0, $creditAmount),
                'bonus_amount'     => $bonus,
                'payment_method'   => $data['payment_method'] ?? 'Cash',
                'description'      => __('members.initial_deposit'),
                'patient_id'       => $patient->id,
                '_who_added'       => Auth::user()->id,
            ]);
        }

        // Record opening fee transaction if any
        if ($openingFee > 0) {
            MemberTransaction::create([
                'transaction_no'   => MemberTransaction::generateTransactionNo(),
                'transaction_type' => 'Adjustment',
                'amount'           => $openingFee,
                'balance_before'   => $initialBalance,
                'balance_after'    => max(0, $creditAmount),
                'payment_method'   => $data['payment_method'] ?? 'Cash',
                'description'      => __('members.opening_fee_label'),
                'patient_id'       => $patient->id,
                '_who_added'       => Auth::user()->id,
            ]);
        }

        // Referral points
        if (!empty($data['referred_by']) && SystemSetting::get('member.referral_bonus_enabled', false)) {
            $referrer = Patient::find($data['referred_by']);
            if ($referrer && $referrer->member_status === 'Active' && $level->referral_points > 0) {
                $patient->update(['referred_by' => $data['referred_by']]);
                $referrer->member_points = ($referrer->member_points ?? 0) + $level->referral_points;
                $referrer->save();

                MemberTransaction::create([
                    'transaction_no'   => MemberTransaction::generateTransactionNo(),
                    'transaction_type' => 'Points',
                    'amount'           => 0,
                    'balance_before'   => $referrer->member_balance,
                    'balance_after'    => $referrer->member_balance,
                    'points_change'    => $level->referral_points,
                    'description'      => __('members.referral_bonus_awarded') . ': ' . $patient->full_name,
                    'patient_id'       => $referrer->id,
                    '_who_added'       => Auth::user()->id,
                ]);
            }
        }

        // Audit log
        MemberAuditLog::log($patient->id, 'register', 'member_level_id', null, $data['member_level_id']);

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
        $patient = Patient::with('memberLevel')->findOrFail($id);

        if ($patient->member_status !== 'Active') {
            return ['message' => __('members.not_active_member'), 'status' => false];
        }

        $amount = (float) $data['amount'];
        $bonus = $patient->memberLevel ? $patient->memberLevel->calculateBonus($amount) : 0;
        $totalCredit = $amount + $bonus;

        $balanceBefore = (float) $patient->member_balance;
        $balanceAfter = $balanceBefore + $totalCredit;

        $patient->update(['member_balance' => $balanceAfter]);

        MemberTransaction::create([
            'transaction_no'   => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Deposit',
            'amount'           => $amount,
            'balance_before'   => $balanceBefore,
            'balance_after'    => $balanceAfter,
            'bonus_amount'     => $bonus,
            'payment_method'   => $data['payment_method'],
            'description'      => $data['description'] ?? __('members.balance_deposit'),
            'patient_id'       => $patient->id,
            '_who_added'       => Auth::user()->id,
        ]);

        // Audit log
        MemberAuditLog::log($patient->id, 'deposit', 'member_balance', $balanceBefore, $balanceAfter);

        return [
            'message'     => __('members.deposit_successful'),
            'status'      => true,
            'new_balance' => $balanceAfter,
            'bonus'       => $bonus,
        ];
    }

    /**
     * Refund from member balance.
     *
     * @return array{message: string, status: bool}
     */
    public function refund(int $id, array $data): array
    {
        $patient = Patient::with('memberLevel')->findOrFail($id);

        if ($patient->member_status !== 'Active') {
            return ['message' => __('members.not_active_member'), 'status' => false];
        }

        $amount = (float) $data['amount'];
        $balanceBefore = (float) $patient->member_balance;

        if ($amount > $balanceBefore) {
            return ['message' => __('members.refund_exceeds_balance'), 'status' => false];
        }

        $balanceAfter = $balanceBefore - $amount;

        $patient->update(['member_balance' => $balanceAfter]);

        MemberTransaction::create([
            'transaction_no'   => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Refund',
            'amount'           => $amount,
            'balance_before'   => $balanceBefore,
            'balance_after'    => $balanceAfter,
            'payment_method'   => $data['payment_method'],
            'description'      => $data['description'] ?? __('members.balance_refund'),
            'patient_id'       => $patient->id,
            '_who_added'       => Auth::user()->id,
        ]);

        MemberAuditLog::log($patient->id, 'refund', 'member_balance', $balanceBefore, $balanceAfter);

        return [
            'message'     => __('members.refund_successful'),
            'status'      => true,
            'new_balance' => $balanceAfter,
        ];
    }

    /**
     * Exchange member points for balance.
     *
     * @return array{message: string, status: bool}
     */
    public function exchangePoints(int $id, int $points): array
    {
        if (!SystemSetting::get('member.points_exchange_enabled', true)) {
            return ['message' => __('members.exchange_disabled'), 'status' => false];
        }

        if (!SystemSetting::get('member.points_enabled', true)) {
            return ['message' => __('members.points_disabled'), 'status' => false];
        }

        $patient = Patient::findOrFail($id);

        if ($patient->member_status !== 'Active') {
            return ['message' => __('members.not_active_member'), 'status' => false];
        }

        if (($patient->member_points ?? 0) < $points) {
            return ['message' => __('members.insufficient_points'), 'status' => false];
        }

        $exchangeRate = (int) SystemSetting::get('member.points_exchange_rate', 100);
        $amount = round($points / $exchangeRate, 2);

        $balanceBefore = (float) $patient->member_balance;
        $balanceAfter = $balanceBefore + $amount;

        $patient->member_points = $patient->member_points - $points;
        $patient->member_balance = $balanceAfter;
        $patient->save();

        MemberTransaction::create([
            'transaction_no'   => MemberTransaction::generateTransactionNo(),
            'transaction_type' => 'Points',
            'amount'           => $amount,
            'balance_before'   => $balanceBefore,
            'balance_after'    => $balanceAfter,
            'points_change'    => -$points,
            'payment_method'   => null,
            'description'      => __('members.points_exchange_desc'),
            'patient_id'       => $patient->id,
            '_who_added'       => Auth::user()->id,
        ]);

        MemberAuditLog::log($patient->id, 'points_exchange', 'member_points', $balanceBefore, $balanceAfter);

        return [
            'message' => __('members.exchange_successful', ['points' => $points, 'amount' => $amount]),
            'status'  => true,
        ];
    }

    /**
     * Check and auto-upgrade member based on total consumption.
     */
    public function checkAndUpgrade(Patient $patient): bool
    {
        if ($patient->member_status !== 'Active' || !$patient->member_level_id) {
            return false;
        }

        $levels = MemberLevel::active()
            ->ordered()
            ->where('min_consumption', '>', 0)
            ->orderBy('min_consumption', 'desc')
            ->get();

        foreach ($levels as $level) {
            if (($patient->total_consumption ?? 0) >= $level->min_consumption
                && $level->id !== $patient->member_level_id
                && $level->min_consumption > ($patient->memberLevel->min_consumption ?? 0)) {

                $oldLevelId = $patient->member_level_id;
                $oldLevelName = $patient->memberLevel->name ?? '-';

                $patient->member_level_id = $level->id;
                $patient->save();

                MemberAuditLog::log(
                    $patient->id,
                    'upgrade',
                    'member_level_id',
                    $oldLevelId,
                    $level->id
                );

                return true;
            }
        }

        return false;
    }

    // ─── Shared Card Holders ────────────────────────────────────────

    /**
     * Add a shared card holder.
     */
    public function addSharedHolder(int $primaryPatientId, int $sharedPatientId, string $relationship): array
    {
        if ($primaryPatientId === $sharedPatientId) {
            return ['message' => __('members.cannot_share_self'), 'status' => false];
        }

        $exists = \App\MemberSharedHolder::where('primary_patient_id', $primaryPatientId)
            ->where('shared_patient_id', $sharedPatientId)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return ['message' => __('members.shared_holder_exists'), 'status' => false];
        }

        \App\MemberSharedHolder::create([
            'primary_patient_id' => $primaryPatientId,
            'shared_patient_id'  => $sharedPatientId,
            'relationship'       => $relationship,
            'is_active'          => true,
            '_who_added'         => Auth::user()->id,
        ]);

        MemberAuditLog::log($primaryPatientId, 'add_shared', 'shared_patient_id', null, $sharedPatientId);

        return ['message' => __('members.shared_holder_added'), 'status' => true];
    }

    /**
     * Remove a shared card holder.
     */
    public function removeSharedHolder(int $holderId): array
    {
        $holder = \App\MemberSharedHolder::findOrFail($holderId);

        MemberAuditLog::log($holder->primary_patient_id, 'remove_shared', 'shared_patient_id', $holder->shared_patient_id, null);

        $holder->delete();

        return ['message' => __('members.shared_holder_removed'), 'status' => true];
    }

    /**
     * Get shared card holders for a member.
     */
    public function getSharedHolders(int $patientId): Collection
    {
        return DB::table('member_shared_holders')
            ->join('patients', 'patients.id', 'member_shared_holders.shared_patient_id')
            ->where('member_shared_holders.primary_patient_id', $patientId)
            ->whereNull('member_shared_holders.deleted_at')
            ->select(
                'member_shared_holders.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.phone_no'
            )
            ->get();
    }

    /**
     * Resolve the primary member for a patient (for shared card payment).
     * Returns the patient itself if they are a primary member, or their card holder's patient.
     */
    public function resolvePrimaryMember(int $patientId): Patient
    {
        $holder = \App\MemberSharedHolder::where('shared_patient_id', $patientId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($holder) {
            return Patient::findOrFail($holder->primary_patient_id);
        }

        return Patient::findOrFail($patientId);
    }

    /**
     * Get audit logs for a member.
     */
    public function getAuditLogs(int $patientId): Collection
    {
        return DB::table('member_audit_logs')
            ->leftJoin('users', 'users.id', 'member_audit_logs._who_added')
            ->where('member_audit_logs.patient_id', $patientId)
            ->orderBy('member_audit_logs.created_at', 'desc')
            ->select(
                'member_audit_logs.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(users.surname, users.othername) as operator_name"
                    : "CONCAT(users.surname, ' ', users.othername) as operator_name")
            )
            ->get();
    }

    // ─── Password ───────────────────────────────────────────────────

    /**
     * Set member password.
     */
    public function setPassword(int $patientId, string $password): array
    {
        $patient = Patient::findOrFail($patientId);

        $patient->update(['member_password' => bcrypt($password)]);

        MemberAuditLog::log($patientId, 'password_change');

        return ['message' => __('members.password_set_successfully'), 'status' => true];
    }

    /**
     * Verify member password.
     */
    public function verifyPassword(int $patientId, string $password): bool
    {
        $patient = Patient::findOrFail($patientId);

        if (!$patient->member_password) {
            return false;
        }

        return \Illuminate\Support\Facades\Hash::check($password, $patient->member_password);
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
            return MemberLevel::orderBy('sort_order')
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
            'name'                        => $data['name'],
            'code'                        => $data['code'],
            'color'                       => $data['color'] ?? '#999999',
            'discount_rate'               => $data['discount_rate'],
            'min_consumption'             => $data['min_consumption'] ?? 0,
            'points_rate'                 => $data['points_rate'] ?? 1,
            'benefits'                    => $data['benefits'] ?? null,
            'sort_order'                  => $data['sort_order'] ?? 0,
            'is_default'                  => $data['is_default'] ?? false,
            'is_active'                   => $data['is_active'] ?? true,
            'opening_fee'                 => $data['opening_fee'] ?? 0,
            'min_initial_deposit'         => $data['min_initial_deposit'] ?? 0,
            'deposit_bonus_rules'         => $data['deposit_bonus_rules'] ?? null,
            'referral_points'             => $data['referral_points'] ?? 0,
            'payment_method_points_rates' => $data['payment_method_points_rates'] ?? null,
            '_who_added'                  => Auth::user()->id,
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
            'name'                        => $data['name'],
            'code'                        => $data['code'],
            'color'                       => $data['color'] ?? '#999999',
            'discount_rate'               => $data['discount_rate'],
            'min_consumption'             => $data['min_consumption'] ?? 0,
            'points_rate'                 => $data['points_rate'] ?? 1,
            'benefits'                    => $data['benefits'] ?? null,
            'sort_order'                  => $data['sort_order'] ?? 0,
            'is_default'                  => $data['is_default'] ?? false,
            'is_active'                   => $data['is_active'] ?? true,
            'opening_fee'                 => $data['opening_fee'] ?? 0,
            'min_initial_deposit'         => $data['min_initial_deposit'] ?? 0,
            'deposit_bonus_rules'         => $data['deposit_bonus_rules'] ?? null,
            'referral_points'             => $data['referral_points'] ?? 0,
            'payment_method_points_rates' => $data['payment_method_points_rates'] ?? null,
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

    // ─── DataTable builders ─────────────────────────────────────

    /**
     * Build DataTables response for the member index page.
     */
    public function buildIndexDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('patient_name', function ($row) {
                return NameHelper::join($row->surname, $row->othername);
            })
            ->addColumn('levelBadge', function ($row) {
                if ($row->level_name) {
                    return '<span class="label" style="background-color:' . e($row->level_color) . '">' . e($row->level_name) . '</span>';
                }
                return '-';
            })
            ->addColumn('statusBadge', function ($row) {
                $class = 'default';
                if ($row->member_status == 'Active') $class = 'success';
                elseif ($row->member_status == 'Expired') $class = 'danger';
                return '<span class="label label-' . $class . '">' . __('members.status_' . strtolower($row->member_status)) . '</span>';
            })
            ->addColumn('phone', function ($row) {
                return $row->phone_no ?? '-';
            })
            ->addColumn('discountDisplay', function ($row) {
                if ($row->discount_rate && $row->discount_rate < 100) {
                    return number_format($row->discount_rate / 10, 1) . __('members.discount_unit');
                }
                return '-';
            })
            ->addColumn('balance', function ($row) {
                return number_format($row->member_balance, 2);
            })
            ->addColumn('totalConsumption', function ($row) {
                return number_format($row->total_consumption ?? 0, 2);
            })
            ->addColumn('expiryDate', function ($row) {
                return $row->member_expiry ? \Carbon\Carbon::parse($row->member_expiry)->format('Y-m-d') : '-';
            })
            ->addColumn('viewBtn', function ($row) {
                return '<a href="' . url('members/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
            })
            ->addColumn('depositBtn', function ($row) {
                return '<a href="#" onclick="depositMember(' . $row->id . ')" class="btn btn-success btn-sm">' . __('members.deposit') . '</a>';
            })
            ->addColumn('editBtn', function ($row) {
                return '<a href="#" onclick="editMember(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
            })
            ->rawColumns(['levelBadge', 'statusBadge', 'viewBtn', 'depositBtn', 'editBtn'])
            ->make(true);
    }

    /**
     * Build DataTables response for member transactions.
     */
    public function buildTransactionsDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('typeBadge', function ($row) {
                $class = 'default';
                if ($row->transaction_type == 'Deposit') $class = 'success';
                elseif ($row->transaction_type == 'Consumption') $class = 'warning';
                elseif ($row->transaction_type == 'Refund') $class = 'info';
                return '<span class="label label-' . $class . '">' . __('members.type_' . strtolower($row->transaction_type)) . '</span>';
            })
            ->addColumn('amountFormatted', function ($row) {
                $prefix = in_array($row->transaction_type, ['Deposit', 'Refund']) ? '+' : '-';
                $class = in_array($row->transaction_type, ['Deposit', 'Refund']) ? 'text-success' : 'text-danger';
                return '<span class="' . $class . '">' . $prefix . number_format($row->amount, 2) . '</span>';
            })
            ->rawColumns(['typeBadge', 'amountFormatted'])
            ->make(true);
    }

    /**
     * Build DataTables response for member levels.
     */
    public function buildLevelsDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('colorBadge', function ($row) {
                return '<span class="label" style="background-color:' . e($row->color) . '">' . e($row->name) . '</span>';
            })
            ->addColumn('discountDisplay', function ($row) {
                if ($row->discount_rate < 100) {
                    return number_format($row->discount_rate / 10, 1) . __('members.discount_unit');
                }
                return __('members.no_discount');
            })
            ->addColumn('minConsumptionDisplay', function ($row) {
                if ($row->min_consumption > 0) {
                    return '<span class="text-primary">&ge; ' . number_format($row->min_consumption, 0) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('statusBadge', function ($row) {
                $class = $row->is_active ? 'success' : 'default';
                $text = $row->is_active ? __('common.active') : __('common.inactive');
                return '<span class="label label-' . $class . '">' . $text . '</span>';
            })
            ->addColumn('editBtn', function ($row) {
                return '<a href="#" onclick="editLevel(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
            })
            ->addColumn('deleteBtn', function ($row) {
                return '<a href="#" onclick="deleteLevel(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
            })
            ->rawColumns(['colorBadge', 'minConsumptionDisplay', 'statusBadge', 'editBtn', 'deleteBtn'])
            ->make(true);
    }
}
