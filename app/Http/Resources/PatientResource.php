<?php

namespace App\Http\Resources;

use App\AccessLog;
use App\Services\DataMaskingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reveal = $request->boolean('unmask') && $request->user()?->can('view-sensitive-data');

        if ($reveal) {
            AccessLog::log('Patient:api_unmask', 'Patient', $this->id);
        }

        return [
            'id'             => $this->id,
            'patient_no'     => $this->patient_no,
            'patient_code'   => $this->patient_code,
            'full_name'      => $reveal ? $this->full_name : DataMaskingService::maskName($this->full_name),
            'surname'        => $reveal ? $this->surname : DataMaskingService::maskName($this->surname),
            'othername'      => $reveal ? $this->othername : DataMaskingService::maskName($this->othername),
            'gender'         => $this->gender,
            'dob'            => $this->dob,
            'date_of_birth'  => $this->date_of_birth ? $this->date_of_birth->toIso8601String() : null,
            'age'            => $this->age,
            'phone_no'       => $reveal ? $this->phone_no : DataMaskingService::maskPhone($this->phone_no),
            'alternative_no' => $reveal ? $this->alternative_no : DataMaskingService::maskPhone($this->alternative_no),
            'email'          => $reveal ? $this->email : DataMaskingService::maskEmail($this->email),
            'address'        => $reveal ? $this->address : DataMaskingService::maskAddress($this->address),
            'ethnicity'      => $this->ethnicity,
            'marital_status' => $this->marital_status,
            'education'      => $this->education,
            'blood_type'     => $this->blood_type,
            'profession'     => $this->profession,
            'nin'            => $reveal ? $this->nin : DataMaskingService::maskNin($this->nin),
            'photo'          => $this->photo,
            'status'         => $this->status,
            'tags'           => $this->tags,
            'notes'          => $this->notes,

            // Insurance
            'has_insurance'        => $this->has_insurance,
            'insurance_company_id' => $this->insurance_company_id,
            'insurance_company'    => $this->whenLoaded('insureanceCompany', fn () => $this->insureanceCompany?->name),

            // Health info
            'drug_allergies'         => $this->drug_allergies,
            'drug_allergies_other'   => $this->drug_allergies_other,
            'systemic_diseases'      => $this->systemic_diseases,
            'systemic_diseases_other' => $this->systemic_diseases_other,
            'current_medication'     => $this->current_medication,
            'medication_history'     => $this->medication_history,
            'is_pregnant'            => $this->is_pregnant,
            'is_breastfeeding'       => $this->is_breastfeeding,

            // Emergency contact
            'next_of_kin'         => $reveal ? $this->next_of_kin : DataMaskingService::maskName($this->next_of_kin),
            'next_of_kin_no'      => $reveal ? $this->next_of_kin_no : DataMaskingService::maskPhone($this->next_of_kin_no),
            'next_of_kin_address' => $reveal ? $this->next_of_kin_address : DataMaskingService::maskAddress($this->next_of_kin_address),

            // Membership
            'member_no'      => $this->member_no,
            'member_level_id' => $this->member_level_id,
            'member_balance'  => $this->member_balance,
            'member_points'   => $this->member_points,
            'member_status'   => $this->member_status,
            'member_since'    => $this->member_since ? $this->member_since->toIso8601String() : null,
            'member_expiry'   => $this->member_expiry ? $this->member_expiry->toIso8601String() : null,

            'source_id'  => $this->source_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
