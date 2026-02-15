<?php

namespace App\Services;

use App\SalaryAdvance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryAdvanceService
{
    /**
     * Get all salary advances for DataTables listing.
     */
    public function getList(): Collection
    {
        return DB::table('salary_advances')
            ->leftJoin('users', 'users.id', 'salary_advances.employee_id')
            ->leftJoin('users as LoggedInUser', 'LoggedInUser.id', 'salary_advances._who_added')
            ->whereNull('salary_advances.deleted_at')
            ->select('salary_advances.*', 'users.surname', 'users.othername', 'LoggedInUser.othername as LoggedInUser')
            ->orderBy('salary_advances.id', 'desc')
            ->get();
    }

    /**
     * Create a new salary advance record.
     */
    public function create(array $input): ?SalaryAdvance
    {
        return SalaryAdvance::create([
            'payment_classification' => $input['payment_classification'],
            'employee_id' => $input['employee'],
            'advance_month' => $input['advance_month'],
            'advance_amount' => $input['amount'],
            'payment_method' => $input['payment_method'],
            'payment_date' => $input['payment_date'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a salary advance by ID with employee info.
     */
    public function find(int $id)
    {
        return DB::table('salary_advances')
            ->join('users', 'users.id', 'salary_advances.employee_id')
            ->where('salary_advances.id', $id)
            ->select('salary_advances.*', 'users.surname', 'users.othername')
            ->first();
    }

    /**
     * Update an existing salary advance.
     */
    public function update(int $id, array $input): bool
    {
        return (bool) SalaryAdvance::where('id', $id)->update([
            'payment_classification' => $input['payment_classification'],
            'employee_id' => $input['employee'],
            'advance_month' => $input['advance_month'],
            'advance_amount' => $input['amount'],
            'payment_method' => $input['payment_method'],
            'payment_date' => $input['payment_date'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a salary advance (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) SalaryAdvance::where('id', $id)->delete();
    }
}
