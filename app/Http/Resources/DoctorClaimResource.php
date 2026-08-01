<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'claim_amount'     => $this->claim_amount,
            'insurance_amount' => $this->insurance_amount,
            'cash_amount'      => $this->cash_amount,
            'claim_rate_id'    => $this->claim_rate_id,
            'appointment_id'   => $this->appointment_id,
            'status'           => $this->status ?? null,
            'total_claims'     => $this->total_claims ?? null,
            'payment_balance'  => $this->payment_balance ?? null,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
