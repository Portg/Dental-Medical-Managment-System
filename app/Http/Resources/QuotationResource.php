<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'quotation_no' => $this->quotation_no,
            'patient_id'   => $this->patient_id,
            'items'        => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'                 => $item->id,
                'qty'                => $item->qty,
                'amount'             => $item->amount,
                'medical_service_id' => $item->medical_service_id,
                'service_name'       => $item->medical_service?->name,
            ])),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
