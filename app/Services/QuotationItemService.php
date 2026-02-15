<?php

namespace App\Services;

use App\QuotationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationItemService
{
    /**
     * Get quotation items for a given quotation, for DataTables listing.
     */
    public function getListByQuotation(int $quotationId): Collection
    {
        return DB::table('quotation_items')
            ->join('medical_services', 'medical_services.id', 'quotation_items.medical_service_id')
            ->join('users', 'users.id', 'quotation_items._who_added')
            ->whereNull('quotation_items.deleted_at')
            ->where('quotation_items.quotation_id', $quotationId)
            ->select('quotation_items.*', 'medical_services.name', 'users.othername')
            ->orderBy('quotation_items.id', 'desc')
            ->get();
    }

    /**
     * Create a new quotation item.
     */
    public function create(array $input): ?QuotationItem
    {
        return QuotationItem::create([
            'qty' => $input['qty'],
            'price' => $input['price'],
            'medical_service_id' => $input['medical_service_id'],
            'tooth_no' => $input['tooth_no'] ?? null,
            'quotation_id' => $input['quotation_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a quotation item by ID with service info.
     */
    public function find(int $id)
    {
        return DB::table('quotation_items')
            ->join('medical_services', 'medical_services.id', 'quotation_items.medical_service_id')
            ->where('quotation_items.id', $id)
            ->select('quotation_items.*', 'medical_services.name')
            ->first();
    }

    /**
     * Update an existing quotation item.
     */
    public function update(int $id, array $input): bool
    {
        return (bool) QuotationItem::where('id', $id)->update([
            'qty' => $input['qty'],
            'price' => $input['price'],
            'medical_service_id' => $input['medical_service_id'],
            'tooth_no' => $input['tooth_no'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a quotation item (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) QuotationItem::where('id', $id)->delete();
    }
}
