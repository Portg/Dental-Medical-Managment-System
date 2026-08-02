<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'prescription_no'   => $this->prescription_no,
            'drug'              => $this->drug,
            'qty'               => $this->qty,
            'directions'        => $this->directions,
            'status'            => $this->status,
            'prescription_date' => $this->dateOnly($this->prescription_date),
            'expiry_date'       => $this->dateOnly($this->expiry_date),
            'refills_allowed'   => $this->refills_allowed,
            'refills_used'      => $this->refills_used,
            'can_refill'        => $this->can_refill,
            'is_expired'        => $this->is_expired,
            'doctor_signature'  => $this->doctor_signature,
            'notes'             => $this->notes,
            'appointment_id'    => $this->appointment_id,
            'medical_case_id'   => $this->medical_case_id,
            'patient_id'        => $this->patient_id,
            'patient'           => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'full_name' => $this->patient->full_name,
            ]),
            'doctor_id'         => $this->doctor_id,
            'doctor'            => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name,
            ]),
            'items'             => $this->whenLoaded('items'),
            'created_at'        => $this->dateTime($this->created_at),
            'updated_at'        => $this->dateTime($this->updated_at),
        ];
    }
}
