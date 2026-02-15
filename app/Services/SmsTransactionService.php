<?php

namespace App\Services;

use App\SmsTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmsTransactionService
{
    /**
     * Get SMS transaction list for DataTables.
     */
    public function getList(array $filters): Collection
    {
        $query = DB::table('sms_transactions')
            ->leftJoin('users', 'users.id', 'sms_transactions._who_added')
            ->whereNull('sms_transactions.deleted_at')
            ->where('type', 'topup')
            ->select('sms_transactions.*', 'users.surname', 'users.othername')
            ->orderBy('sms_transactions.id', 'desc');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE(sms_transactions.created_at)'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        }

        return $query->get();
    }

    /**
     * Get total credit loaded (topup).
     */
    public function getCreditLoaded(): float
    {
        return (float) SmsTransaction::where('type', 'topup')->sum('amount');
    }

    /**
     * Get total credit used (non-topup).
     */
    public function getCreditUsed(): float
    {
        return (float) SmsTransaction::where('type', '!=', 'topup')->sum('amount');
    }

    /**
     * Get current balance.
     */
    public function getCurrentBalance(): float
    {
        return $this->getCreditLoaded() - $this->getCreditUsed();
    }
}
