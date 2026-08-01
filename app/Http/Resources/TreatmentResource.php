<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'clinical_notes' => $this->clinical_notes,
            'treatment'      => $this->treatment,
            'appointment_id' => $this->appointment_id,
            'added_by'       => $this->whenLoaded('addedBy', fn () => $this->addedBy?->full_name),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
