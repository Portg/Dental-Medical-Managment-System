<?php

namespace App\Services;

use App\AccountingEquation;
use App\ChartOfAccountCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ChartOfAccountCategoryService
{
    /**
     * Get all categories with their accounting equation relationship.
     */
    public function getAllCategories(): Collection
    {
        return ChartOfAccountCategory::with('accountingEquation')->get();
    }

    /**
     * Get all accounting equations for dropdown.
     */
    public function getAccountingEquations(): \Illuminate\Support\Collection
    {
        return AccountingEquation::orderBy('sort_by')->get();
    }

    /**
     * Create a new chart of account category.
     */
    public function createCategory(array $data): ?ChartOfAccountCategory
    {
        return ChartOfAccountCategory::create([
            'name' => $data['name'],
            'accounting_equation_id' => $data['accounting_equation_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a category by ID with its accounting equation.
     */
    public function findCategory(int $id): ?ChartOfAccountCategory
    {
        return ChartOfAccountCategory::with('accountingEquation')
            ->where('id', $id)
            ->first();
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(int $id, array $data): bool
    {
        return (bool) ChartOfAccountCategory::where('id', $id)->update([
            'name' => $data['name'],
            'accounting_equation_id' => $data['accounting_equation_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a category (soft-delete).
     */
    public function deleteCategory(int $id): bool
    {
        return (bool) ChartOfAccountCategory::where('id', $id)->delete();
    }
}
