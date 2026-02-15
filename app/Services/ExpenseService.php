<?php

namespace App\Services;

use App\ChartOfAccountItem;
use App\Expense;
use App\ExpenseCategory;
use App\ExpenseItem;
use App\ExpensePayment;
use App\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Get filtered expense list for DataTables.
     */
    public function getExpenseList(array $filters): Collection
    {
        $query = DB::table('expenses')
            ->join('suppliers', 'suppliers.id', 'expenses.supplier_id')
            ->join('users', 'users.id', 'expenses._who_added')
            ->whereNull('expenses.deleted_at')
            ->select('expenses.*', 'suppliers.name as supplier_name', 'users.surname', 'users.othername')
            ->orderBy('expenses.updated_at', 'desc');

        if (!empty($filters['search'])) {
            $query->where('suppliers.name', 'like', '%' . $filters['search'] . '%');
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE(expenses.created_at)'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        }

        return $query->get();
    }

    /**
     * Get chart of account items (non-income, non-cash).
     */
    public function getChartOfAccounts(): Collection
    {
        return ChartOfAccountItem::leftJoin('chart_of_account_categories', 'chart_of_account_categories.id',
                'chart_of_account_items.chart_of_account_category_id')
            ->leftJoin('accounting_equations', 'accounting_equations.id',
                'chart_of_account_categories.accounting_equation_id')
            ->whereNull('chart_of_account_items.deleted_at')
            ->where('accounting_equations.name', '!=', 'Income')
            ->where('chart_of_account_categories.name', '!=', 'Cash and Bank')
            ->select('chart_of_account_items.*')
            ->get();
    }

    /**
     * Get payment accounts (Cash and Bank).
     */
    public function getPaymentAccounts(): Collection
    {
        return ChartOfAccountItem::leftJoin('chart_of_account_categories', 'chart_of_account_categories.id',
                'chart_of_account_items.chart_of_account_category_id')
            ->leftJoin('accounting_equations', 'accounting_equations.id',
                'chart_of_account_categories.accounting_equation_id')
            ->whereNull('chart_of_account_items.deleted_at')
            ->where('chart_of_account_categories.name', '=', 'Cash and Bank')
            ->select('chart_of_account_items.*')
            ->get();
    }

    /**
     * Get expense detail with supplier info.
     */
    public function getExpenseDetail(int $expenseId): ?object
    {
        return DB::table('expenses')
            ->join('suppliers', 'suppliers.id', 'expenses.supplier_id')
            ->where('expenses.id', $expenseId)
            ->select('expenses.*', 'suppliers.name as supplier_name')
            ->first();
    }

    /**
     * Calculate total amount of an expense (sum of qty * price).
     */
    public function totalAmount(int $expenseId): float
    {
        return (float) ExpenseItem::where('expense_id', $expenseId)->sum(DB::raw('qty * price'));
    }

    /**
     * Calculate purchase balance (total - paid).
     */
    public function purchaseBalance(int $expenseId): float
    {
        $invoiceAmount = ExpenseItem::where('expense_id', $expenseId)->sum(DB::raw('qty * price'));
        $amountPaid = ExpensePayment::where('expense_id', $expenseId)->sum('amount');

        return $invoiceAmount - $amountPaid;
    }

    /**
     * Create or get an existing supplier by name.
     */
    public function createOrGetSupplier(string $supplierName): int
    {
        $existing = Supplier::where('name', $supplierName)->first();
        if ($existing) {
            return $existing->id;
        }

        $new = Supplier::create(['name' => $supplierName, '_who_added' => Auth::User()->id]);
        return $new->id;
    }

    /**
     * Create or get an existing expense category by name.
     */
    public function createOrGetExpenseCategory(string $categoryName, int $chartOfAccountItemId): int
    {
        $existing = ExpenseCategory::where('name', $categoryName)->first();
        if ($existing) {
            return $existing->id;
        }

        $new = ExpenseCategory::create([
            'name' => $categoryName,
            'chart_of_account_item_id' => $chartOfAccountItemId,
            '_who_added' => Auth::User()->id,
        ]);
        return $new->id;
    }

    /**
     * Create an expense with its items.
     */
    public function createExpense(array $data, array $items): ?Expense
    {
        $supplierId = $this->createOrGetSupplier($data['supplier']);

        $expense = Expense::create([
            'purchase_no' => Expense::PurchaseNo(),
            'supplier_id' => $supplierId,
            'purchase_date' => $data['purchase_date'],
            'branch_id' => Auth::User()->branch_id,
            '_who_added' => Auth::User()->id,
        ]);

        if ($expense) {
            foreach ($items as $value) {
                $itemCategoryId = $this->createOrGetExpenseCategory($value['item'], $value['expense_category']);
                ExpenseItem::create([
                    'expense_category_id' => $itemCategoryId,
                    'description' => $value['description'],
                    'qty' => $value['qty'],
                    'price' => $value['price'],
                    'expense_id' => $expense->id,
                    '_who_added' => Auth::User()->id,
                ]);
            }
        }

        return $expense;
    }

    /**
     * Delete an expense (soft-delete).
     */
    public function deleteExpense(int $id): bool
    {
        return (bool) Expense::where('id', $id)->delete();
    }

    /**
     * Get expense export data.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('expense_items')
            ->join('expenses', 'expenses.id', 'expense_items.expense_id')
            ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
            ->join('chart_of_account_items', 'chart_of_account_items.id', 'expense_categories.chart_of_account_item_id')
            ->whereNull('expense_items.deleted_at')
            ->whereNull('expenses.deleted_at')
            ->select('expense_items.*', 'expense_categories.name as item_name', 'chart_of_account_items.name as budget_line')
            ->orderBy('expense_items.id', 'ASC');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(expense_items.created_at)'), [$from, $to]);
        }

        return $query->get();
    }
}
