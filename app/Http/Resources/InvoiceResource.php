<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'invoice_no'         => $this->invoice_no,
            'invoice_date'       => $this->invoice_date?->toIso8601String(),
            'invoice_type'       => $this->invoice_type,
            'status'             => $this->status,
            'payment_status'     => $this->payment_status,

            // Amounts
            'subtotal'           => $this->subtotal,
            'discount_amount'    => $this->discount_amount,
            'tax_amount'         => $this->tax_amount,
            'total_amount'       => $this->total_amount,
            'paid_amount'        => $this->paid_amount,
            'outstanding_amount' => $this->outstanding_amount,
            'due_date'           => $this->due_date?->toIso8601String(),
            'notes'              => $this->notes,

            // Discounts
            'member_discount_rate'    => $this->member_discount_rate,
            'member_discount_amount'  => $this->member_discount_amount,
            'item_discount_amount'    => $this->item_discount_amount,
            'order_discount_rate'     => $this->order_discount_rate,
            'order_discount_amount'   => $this->order_discount_amount,
            'coupon_id'               => $this->coupon_id,
            'coupon_discount_amount'  => $this->coupon_discount_amount,

            // Discount approval
            'discount_approval_status' => $this->discount_approval_status,
            'discount_approved_at'     => $this->discount_approved_at?->toIso8601String(),

            // Credit
            'is_credit'          => $this->is_credit,
            'credit_approved_at' => $this->credit_approved_at?->toIso8601String(),

            // Relations
            'appointment_id'   => $this->appointment_id,
            'patient_id'       => $this->patient_id,
            'patient'          => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'patient_no' => $this->patient->patient_no,
                'full_name' => $this->patient->full_name,
            ]),
            'medical_case_id'  => $this->medical_case_id,

            // Items
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                 => $item->id,
                'medical_service_id' => $item->medical_service_id,
                'service_name'       => $item->medical_service?->name,
                'qty'                => $item->qty,
                'price'              => $item->price,
                'tooth_no'           => $item->tooth_no,
                'doctor_id'          => $item->doctor_id,
            ])),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
