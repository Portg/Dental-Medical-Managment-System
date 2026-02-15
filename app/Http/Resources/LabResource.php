<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'contact'              => $this->contact,
            'phone'                => $this->phone,
            'address'              => $this->address,
            'specialties'          => $this->specialties,
            'avg_turnaround_days'  => $this->avg_turnaround_days,
            'notes'                => $this->notes,
            'is_active'            => (bool) $this->is_active,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
