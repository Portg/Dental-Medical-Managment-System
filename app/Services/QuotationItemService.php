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
            // 列名叫 amount，存的其实是**单价**：表单提交的是 addmore[N][price]
            // （单价），行小计由 qty * 单价算出。原先这里写 'price'，而
            // quotation_items 根本没有这一列、$fillable 里也没有 —— 批量赋值保护
            // 把它静默丢掉，于是每条报价项的金额都是空的。
            'amount' => $input['price'],
            'tooth_no' => $input['tooth_no'] ?? null,
            'medical_service_id' => $input['medical_service_id'],
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
            // 同 create()：列名 amount，存的是单价
            'amount' => $input['price'],
            'tooth_no' => $input['tooth_no'] ?? null,
            'medical_service_id' => $input['medical_service_id'],
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
