<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'case_no'   => $this->case_no,
            'title'     => $this->title,
            'case_date' => $this->case_date?->toIso8601String(),
            'status'    => $this->status,
            'is_draft'  => $this->is_draft,
            'visit_type' => $this->visit_type,

            // SOAP - Subjective
            'chief_complaint'           => $this->chief_complaint,
            'history_of_present_illness' => $this->history_of_present_illness,

            // SOAP - Objective
            'examination'         => $this->examination,
            'examination_teeth'   => $this->examination_teeth,
            'related_teeth'       => $this->related_teeth,
            'related_images'      => $this->related_images,
            'auxiliary_examination' => $this->auxiliary_examination,

            // SOAP - Assessment
            'diagnosis'      => $this->diagnosis,
            'diagnosis_code' => $this->diagnosis_code,

            // SOAP - Plan
            'treatment'          => $this->treatment,
            'treatment_services' => $this->treatment_services,
            'medical_orders'     => $this->medical_orders,

            // Followup
            'next_visit_date'      => $this->next_visit_date?->toIso8601String(),
            'next_visit_note'      => $this->next_visit_note,
            'auto_create_followup' => $this->auto_create_followup,

            // Status tracking
            'closed_date'   => $this->closed_date?->toIso8601String(),
            'closing_notes' => $this->closing_notes,
            'locked_at'     => $this->locked_at?->toIso8601String(),
            'modified_at'   => $this->modified_at?->toIso8601String(),

            // Compliance
            'version_number' => $this->version_number ?? 1,
            'is_locked'      => !is_null($this->locked_at),

            // Signature
            'signature' => $this->signature,
            'signed_at' => $this->signed_at?->toIso8601String(),

            // Relations
            'patient_id' => $this->patient_id,
            'patient'    => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'patient_no' => $this->patient->patient_no,
                'full_name' => $this->patient->full_name,
            ]),
            'doctor_id'  => $this->doctor_id,
            'doctor'     => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
