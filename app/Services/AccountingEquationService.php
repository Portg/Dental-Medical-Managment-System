<?php

namespace App\Services;

use App\AccountingEquation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AccountingEquationService
{
    /**
     * Get all accounting equations ordered by sort_by.
     */
    public function getAllEquations(): Collection
    {
        return AccountingEquation::orderBy('sort_by')->get();
    }

    /**
     * Create a new accounting equation.
     */
    public function createEquation(array $data): ?AccountingEquation
    {
        return AccountingEquation::create([
            'name' => $data['name'],
            'sort_by' => $data['sort_by'],
            'active_tab' => !empty($data['active_tab']),
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find an accounting equation by ID.
     */
    public function findEquation(int $id): ?AccountingEquation
    {
        return AccountingEquation::where('id', $id)->first();
    }

    /**
     * Update an existing accounting equation.
     */
    public function updateEquation(int $id, array $data): bool
    {
        return (bool) AccountingEquation::where('id', $id)->update([
            'name' => $data['name'],
            'sort_by' => $data['sort_by'],
            'active_tab' => !empty($data['active_tab']),
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an accounting equation (soft-delete).
     */
    public function deleteEquation(int $id): bool
    {
        return (bool) AccountingEquation::where('id', $id)->delete();
    }
}
