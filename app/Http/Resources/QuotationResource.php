<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    use FormatsDates;

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
            'created_at'   => $this->dateTime($this->created_at),
            'updated_at'   => $this->dateTime($this->updated_at),
        ];
    }
}
