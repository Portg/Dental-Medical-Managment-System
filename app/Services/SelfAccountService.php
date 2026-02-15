<?php

namespace App\Services;

use App\InvoicePayment;
use App\SelfAccount;
use App\SelfAccountDeposit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SelfAccountService
{
    /**
     * Get account list for DataTables.
     */
    public function getAccountList(?string $search = null): Collection
    {
        $query = DB::table('self_accounts')
            ->leftJoin('users', 'users.id', 'self_accounts._who_added')
            ->whereNull('self_accounts.deleted_at')
            ->select(['self_accounts.*', 'users.surname']);

        if ($search) {
            $query->where('self_accounts.account_holder', 'like', '%' . $search . '%');
        }

        return $query->orderBy('self_accounts.id', 'desc')->get();
    }

    /**
     * Calculate account balance for a given account.
     */
    public function getAccountBalance(int $accountId): float
    {
        $payments = InvoicePayment::where('self_account_id', $accountId)->sum('amount');
        $deposits = SelfAccountDeposit::where('self_account_id', $accountId)->sum('amount');

        return $deposits - $payments;
    }

    /**
     * Search accounts by name (for Select2).
     */
    public function searchAccounts(string $keyword): array
    {
        $data = SelfAccount::where('account_holder', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->account_holder];
        }

        return $formatted;
    }

    /**
     * Get account detail by ID.
     */
    public function find(int $id): ?SelfAccount
    {
        return SelfAccount::where('id', $id)->first();
    }

    /**
     * Create a new self account.
     */
    public function createAccount(array $data): ?SelfAccount
    {
        return SelfAccount::create([
            'account_no' => SelfAccount::AccountNo(),
            'account_holder' => $data['name'],
            'holder_phone_no' => $data['phone_no'] ?? null,
            'holder_email' => $data['email'] ?? null,
            'holder_address' => $data['address'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing self account.
     */
    public function updateAccount(int $id, array $data): bool
    {
        return (bool) SelfAccount::where('id', $id)->update([
            'account_no' => SelfAccount::AccountNo(),
            'account_holder' => $data['name'],
            'holder_phone_no' => $data['phone_no'] ?? null,
            'holder_email' => $data['email'] ?? null,
            'holder_address' => $data['address'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a self account (soft-delete).
     */
    public function deleteAccount(int $id): bool
    {
        return (bool) SelfAccount::where('id', $id)->delete();
    }
}
