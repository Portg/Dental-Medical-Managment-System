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
     * 支出付款方式的取值范围。
     *
     * 前四项与支出付款表单（expenses/payment/create.blade.php）的单选项一一对应；
     * Online Wallet 是历史数据里存在的旧值，保留以免编辑旧记录时被校验挡下。
     *
     * 库里这一列已从 enum 放开成 varchar（见 2026_08_15 的迁移）——枚举挡不住
     * 表单新增选项，只会让「加一种付款方式」变成改表结构。范围由这里把关。
     */
    public const PAYMENT_METHODS = [
        'Cash',
        'Mobile Money',
        'Cheque',
        'Bank Wire Transfer',
        'Online Wallet',
    ];

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
