<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'plan_name'                => $this->plan_name,
            'description'              => $this->description,
            'planned_procedures'       => $this->planned_procedures,
            'related_teeth'            => $this->related_teeth,
            'estimated_cost'           => $this->estimated_cost,
            'actual_cost'              => $this->actual_cost,
            'total_price'              => $this->total_price,
            'discount_rate'            => $this->discount_rate,
            'final_price'              => $this->final_price,
            'status'                   => $this->status,
            'approval_status'          => $this->approval_status,
            'priority'                 => $this->priority,
            'start_date'               => $this->start_date?->toIso8601String(),
            'target_completion_date'    => $this->target_completion_date?->toIso8601String(),
            'actual_completion_date'    => $this->actual_completion_date?->toIso8601String(),
            'completion_notes'         => $this->completion_notes,
            'completion_percentage'    => $this->completion_percentage,
            'medical_case_id'          => $this->medical_case_id,
            'patient_id'               => $this->patient_id,
            'patient'                  => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'items'                    => $this->whenLoaded('items'),
            'stages'                   => $this->whenLoaded('stages'),
            'created_at'               => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
        ];
    }
}
