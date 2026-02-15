<?php

namespace App\Services;

use App\ExpenseItem;
use App\ExpensePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpensePaymentService
{
    /**
     * Get expense payments for a given expense.
     */
    public function getPaymentsByExpense(int $expenseId): Collection
    {
        return ExpensePayment::where('expense_id', $expenseId)
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    /**
     * Calculate supplier balance for an expense.
     */
    public function getSupplierBalance(int $expenseId): array
    {
        $invoiceAmount = ExpenseItem::where('expense_id', $expenseId)->sum(DB::raw('qty * price'));
        $amountPaid = ExpensePayment::where('expense_id', $expenseId)->sum('amount');
        $balance = $invoiceAmount - $amountPaid;

        return [
            'amount' => $balance,
            'today_date' => date('Y-m-d'),
        ];
    }

    /**
     * Get a single payment for editing.
     */
    public function getPaymentForEdit(int $id): ?ExpensePayment
    {
        return ExpensePayment::where('id', $id)->first();
    }

    /**
     * Create a new expense payment.
     */
    public function createPayment(array $data): ?ExpensePayment
    {
        return ExpensePayment::create([
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_account_id' => $data['payment_account'],
            'expense_id' => $data['expense_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an expense payment.
     */
    public function updatePayment(int $id, array $data): bool
    {
        return (bool) ExpensePayment::where('id', $id)->update([
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_account_id' => $data['payment_account'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an expense payment.
     */
    public function deletePayment(int $id): bool
    {
        return (bool) ExpensePayment::where('id', $id)->delete();
    }
}
