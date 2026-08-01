<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'lab_case_no'           => $this->lab_case_no,
            'prosthesis_type'       => $this->prosthesis_type,
            'material'              => $this->material,
            'color_shade'           => $this->color_shade,
            'teeth_positions'       => $this->teeth_positions,
            'special_requirements'  => $this->special_requirements,
            'status'                => $this->status,
            'sent_date'             => $this->sent_date?->format('Y-m-d'),
            'expected_return_date'  => $this->expected_return_date?->format('Y-m-d'),
            'actual_return_date'    => $this->actual_return_date?->format('Y-m-d'),
            'lab_fee'               => (float) $this->lab_fee,
            'patient_charge'        => (float) $this->patient_charge,
            'profit'                => $this->profit,
            'is_overdue'            => $this->is_overdue,
            'quality_rating'        => $this->quality_rating,
            'rework_count'          => $this->rework_count,
            'rework_reason'         => $this->rework_reason,
            'notes'                 => $this->notes,
            'patient_id'            => $this->patient_id,
            'patient'               => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'patient_no' => $this->patient->patient_no,
                'full_name' => $this->patient->surname . $this->patient->othername,
            ]),
            'doctor_id'             => $this->doctor_id,
            'doctor'                => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name ?? $this->doctor->othername,
            ]),
            'lab_id'                => $this->lab_id,
            'lab'                   => $this->whenLoaded('lab', fn () => [
                'id'   => $this->lab->id,
                'name' => $this->lab->name,
            ]),
            'appointment_id'        => $this->appointment_id,
            'medical_case_id'       => $this->medical_case_id,
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
