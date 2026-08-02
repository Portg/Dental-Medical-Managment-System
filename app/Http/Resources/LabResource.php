<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabResource extends JsonResource
{
    use FormatsDates;

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
            'created_at'           => $this->dateTime($this->created_at),
            'updated_at'           => $this->dateTime($this->updated_at),
        ];
    }
}
