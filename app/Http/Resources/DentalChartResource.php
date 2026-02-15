<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tooth'           => $this->tooth,
            'tooth_number'    => $this->tooth_number,
            'tooth_type'      => $this->tooth_type,
            'tooth_status'    => $this->tooth_status,
            'fdi_notation'    => $this->fdi_notation,
            'section'         => $this->section,
            'color'           => $this->color,
            'surface'         => $this->surface,
            'notes'           => $this->notes,
            'appointment_id'  => $this->appointment_id,
            'medical_case_id' => $this->medical_case_id,
            'doctor_id'       => $this->doctor_id,
            'doctor'          => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name,
            ]),
            'changed_at'      => $this->changed_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
