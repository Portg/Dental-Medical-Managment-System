<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'appointment_no'   => $this->appointment_no,
            'start_date'       => $this->dateOnly($this->start_date),
            'end_date'         => $this->dateOnly($this->end_date),
            'start_time'       => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'sort_by'          => $this->dateTime($this->sort_by),
            'appointment_type' => $this->appointment_type,
            'source'           => $this->source,
            'visit_information' => $this->visit_information,
            'status'           => $this->status,
            'notes'            => $this->notes,

            // Confirmation
            'reminder_sent'        => $this->reminder_sent,
            'reminder_sent_at'     => $this->dateTime($this->reminder_sent_at),
            'confirmed_by_patient' => $this->confirmed_by_patient,
            'confirmed_at'         => $this->dateTime($this->confirmed_at),

            // Cancellation
            'cancelled_reason' => $this->cancelled_reason,
            'no_show_count'    => $this->no_show_count,

            // Relations
            'patient_id'      => $this->patient_id,
            'patient'         => $this->whenLoaded('patient', fn () => [
                'id'        => $this->patient->id,
                'patient_no' => $this->patient->patient_no,
                'full_name' => $this->patient->full_name,
                'phone_no'  => $this->patient->phone_no,
            ]),
            'doctor_id'       => $this->doctor_id,
            'doctor'          => $this->whenLoaded('doctor', fn () => [
                'id'        => $this->doctor->id,
                'full_name' => $this->doctor->full_name,
            ]),
            'branch_id'       => $this->branch_id,
            'chair_id'        => $this->chair_id,
            'chair'           => $this->whenLoaded('chair', fn () => [
                'id'   => $this->chair->id,
                'name' => $this->chair->name,
            ]),
            'service_id'      => $this->service_id,
            'service'         => $this->whenLoaded('service', fn () => [
                'id'   => $this->service->id,
                'name' => $this->service->name,
            ]),
            'medical_case_id' => $this->medical_case_id,

            'created_at' => $this->dateTime($this->created_at),
            'updated_at' => $this->dateTime($this->updated_at),
        ];
    }
}
