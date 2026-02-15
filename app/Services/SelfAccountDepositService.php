<?php

namespace App\Services;

use App\SelfAccountDeposit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SelfAccountDepositService
{
    /**
     * Get deposits for a self account, for DataTables.
     */
    public function getList(int $selfAccountId): Collection
    {
        return DB::table('self_account_deposits')
            ->leftJoin('users', 'users.id', 'self_account_deposits._who_added')
            ->whereNull('self_account_deposits.deleted_at')
            ->where('self_account_deposits.self_account_id', $selfAccountId)
            ->select('self_account_deposits.*', 'users.surname as added_by')
            ->orderBy('self_account_deposits.updated_at', 'DESC')
            ->get();
    }

    /**
     * Find a deposit by ID.
     */
    public function find(int $id): ?SelfAccountDeposit
    {
        return SelfAccountDeposit::where('id', $id)->first();
    }

    /**
     * Create a new deposit.
     */
    public function create(array $data): ?SelfAccountDeposit
    {
        return SelfAccountDeposit::create([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'],
            'self_account_id' => $data['self_account_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update a deposit.
     */
    public function update(int $id, array $data): bool
    {
        return (bool) SelfAccountDeposit::where('id', $id)->update([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a deposit (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) SelfAccountDeposit::where('id', $id)->delete();
    }
}
