<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'transaction_no'   => $this->transaction_no,
            'transaction_type' => $this->transaction_type,
            'amount'           => $this->amount,
            'balance_before'   => $this->balance_before,
            'balance_after'    => $this->balance_after,
            'points_change'    => $this->points_change,
            'payment_method'   => $this->payment_method,
            'description'      => $this->description,
            'patient_id'       => $this->patient_id,
            'invoice_id'       => $this->invoice_id,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
