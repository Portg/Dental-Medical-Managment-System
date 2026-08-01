<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'amount'               => $this->amount,
            'payment_method'       => $this->payment_method,
            'account_name'         => $this->account_name,
            'cheque_no'            => $this->cheque_no,
            'bank_name'            => $this->bank_name,
            'payment_date'         => $this->payment_date,
            'invoice_id'           => $this->invoice_id,
            'insurance_company_id' => $this->insurance_company_id,
            'self_account_id'      => $this->self_account_id,
            'branch_id'            => $this->branch_id,
            'added_by'             => $this->whenLoaded('addedBy', fn () => $this->addedBy?->full_name),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
