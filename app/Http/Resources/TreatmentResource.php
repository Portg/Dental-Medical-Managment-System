<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'clinical_notes' => $this->clinical_notes,
            'treatment'      => $this->treatment,
            'appointment_id' => $this->appointment_id,
            'added_by'       => $this->whenLoaded('addedBy', fn () => $this->addedBy?->full_name),
            'created_at'     => $this->dateTime($this->created_at),
            'updated_at'     => $this->dateTime($this->updated_at),
        ];
    }
}
