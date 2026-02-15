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
            ->select('invoice_items.*', 'medical_services.name as service_name')
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
