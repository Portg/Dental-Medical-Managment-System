<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'refund_no'        => $this->refund_no,
            'invoice_id'       => $this->invoice_id,
            'invoice'          => $this->whenLoaded('invoice', fn () => [
                'id'         => $this->invoice->id,
                'invoice_no' => $this->invoice->invoice_no,
            ]),
            'patient_id'       => $this->patient_id,
            'patient'          => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'refund_amount'    => $this->refund_amount,
            'refund_reason'    => $this->refund_reason,
            'refund_date'      => $this->refund_date?->toIso8601String(),
            'refund_method'    => $this->refund_method,
            'approval_status'  => $this->approval_status,
            'approved_by'      => $this->approved_by,
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'branch_id'        => $this->branch_id,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
