<?php

namespace App\Services;

use App\ClaimRate;
use App\DoctorClaim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorModuleClaimService
{
    /**
     * Get claims list for the current doctor.
     */
    public function getClaimsList(): Collection
    {
        return DB::table('doctor_claims')
            ->join('appointments', 'appointments.id', 'doctor_claims.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('doctor_claims.deleted_at')
            ->where('doctor_claims._who_added', Auth::User()->id)
            ->select('doctor_claims.*', 'patients.surname', 'patients.othername')
            ->orderBy('doctor_claims.updated_at', 'desc')
            ->get();
    }

    /**
     * Calculate insurance claim amount for the current doctor.
     */
    public function calculateInsuranceClaim(float $insuranceAmount): float
    {
        $claimRate = $this->getActiveClaimRate();
        return $claimRate->insurance_rate / 100 * $insuranceAmount;
    }

    /**
     * Calculate cash claim amount for the current doctor.
     */
    public function calculateCashClaim(float $cashAmount): float
    {
        $claimRate = $this->getActiveClaimRate();
        return $claimRate->cash_rate / 100 * $cashAmount;
    }

    /**
     * Calculate total claim amount (insurance + cash) for the current doctor.
     */
    public function calculateTotalClaim(float $insuranceAmount, float $cashAmount): float
    {
        return $this->calculateInsuranceClaim($insuranceAmount) + $this->calculateCashClaim($cashAmount);
    }

    /**
     * Get the active claim rate for the current doctor, or null if none.
     */
    public function getActiveClaimRate(): ?object
    {
        return ClaimRate::where(['doctor_id' => Auth::User()->id, 'status' => 'active'])->first();
    }

    /**
     * Create a new doctor claim.
     *
     * @return DoctorClaim|null  Returns null if no active claim rate exists.
     */
    public function createClaim(int $appointmentId, float $amount): ?DoctorClaim
    {
        $claimRate = $this->getActiveClaimRate();
        if ($claimRate === null) {
            return null;
        }

        return DoctorClaim::create([
            'claim_amount' => $amount,
            'appointment_id' => $appointmentId,
            'claim_rate_id' => $claimRate->id,
            '_who_added' => Auth::User()->id,
        ]) ?: null;
    }

    /**
     * Get a claim for editing.
     */
    public function getClaimForEdit(int $id): ?DoctorClaim
    {
        return DoctorClaim::where('id', $id)->first();
    }

    /**
     * Update an existing claim.
     */
    public function updateClaim(int $id, float $amount): bool
    {
        return (bool) DoctorClaim::where('id', $id)->update([
            'claim_amount' => $amount,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a claim (soft-delete).
     */
    public function deleteClaim(int $id): bool
    {
        return (bool) DoctorClaim::where('id', $id)->delete();
    }
}
