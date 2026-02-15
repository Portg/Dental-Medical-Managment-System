<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SelfAccountBillPaymentService
{
    /**
     * Get bill payments for a self account, for DataTables.
     */
    public function getList(int $selfAccountId): Collection
    {
        return DB::table('invoice_payments')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->leftJoin('users', 'users.id', 'invoice_payments._who_added')
            ->whereNull('invoice_payments.deleted_at')
            ->where('invoice_payments.self_account_id', $selfAccountId)
            ->select(
                'invoice_payments.*',
                'invoices.invoice_no',
                'patients.surname',
                'patients.othername',
                'users.surname as added_by'
            )
            ->orderBy('invoice_payments.updated_at', 'DESC')
            ->get();
    }
}
