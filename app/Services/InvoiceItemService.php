<?php

namespace App\Services;

use App\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceItemService
{
    /**
     * Get invoice items for a given invoice.
     */
    public function getItemsByInvoice(int $invoiceId): Collection
    {
        return InvoiceItem::where('invoice_id', $invoiceId)->get();
    }

    /**
     * Get invoice items for a given appointment (doctor invoicing dashboard).
     */
    public function getItemsByAppointment(int $appointmentId): Collection
    {
        return DB::table('invoice_items')
            ->leftJoin('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->leftJoin('invoices', 'invoices.id', 'invoice_items.invoice_id')
            ->whereNull('invoice_items.deleted_at')
            ->where('invoices.appointment_id', $appointmentId)
            ->select(
                'invoice_items.*',
                'medical_services.name as service_name',
                // invoice_items.amount 是 2019 年的遗留列，两条开单路径（createInvoice 与
                // createBillingInvoice）都从未写它，MySQL 非严格模式下补 0 —— 于是诊疗页
                // 「本次已划价」表的金额列一直显示 0。真正的金额在 actual_paid（患者实付），
                // 老数据这两列为 NULL 时退回 price * qty。
                //
                // 用 COALESCE 而不是 NULLIF(x, 0)：actual_paid 真的是 0 的情况是合法的
                // （赠送项目），不该被当成缺值而回退到原价。
                DB::raw('COALESCE(invoice_items.actual_paid, invoice_items.discounted_price, invoice_items.price * invoice_items.qty) as line_amount')
            )
            ->get();
    }

    /**
     * Get a single invoice item with service and doctor details.
     */
    public function getItemForEdit(int $id): ?object
    {
        return DB::table('invoice_items')
            ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->join('users', 'users.id', 'invoice_items.doctor_id')
            ->where('invoice_items.id', $id)
            ->select('invoice_items.*', 'medical_services.name', 'users.surname', 'users.othername')
            ->first();
    }

    /**
     * Update an invoice item.
     */
    public function updateItem(int $id, array $data): bool
    {
        return (bool) InvoiceItem::where('id', $id)->update([
            'qty' => $data['qty'],
            'price' => $data['price'],
            'medical_service_id' => $data['medical_service_id'],
            'doctor_id' => $data['doctor_id'],
            'tooth_no' => $data['tooth_no'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an invoice item.
     */
    public function deleteItem(int $id): bool
    {
        return (bool) InvoiceItem::where('id', $id)->delete();
    }
}
