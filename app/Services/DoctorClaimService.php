<?php

namespace App\Services;

use App\ClaimRate;
use App\DoctorClaim;
use App\DoctorClaimPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorClaimService
{
    /**
     * Get doctor claims list for DataTables.
     */
    public function getClaimsList(): Collection
    {
        return DB::table('doctor_claims')
            ->join('appointments', 'appointments.id', 'doctor_claims.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'doctor_claims._who_added')
            ->whereNull('doctor_claims.deleted_at')
            ->select('doctor_claims.*', 'patients.surname', 'patients.othername', 'users.othername as doctor')
            ->orderBy('doctor_claims.updated_at', 'desc')
            ->get();
    }

    /**
     * Calculate total claims for a given row.
     */
    public function getTotalClaims($row): float
    {
        $insurance = $this->insuranceClaim($row->insurance_amount, $row->_who_added);
        $cash = $this->cashClaim($row->cash_amount, $row->_who_added);

        return $insurance + $cash;
    }

    /**
     * Calculate insurance claim amount based on rate.
     */
    public function insuranceClaim(float $insuranceAmount, int $doctorId): float
    {
        $claimRate = ClaimRate::where(['doctor_id' => $doctorId, 'status' => 'active'])->first();

        if (!$claimRate) {
            return 0;
        }

        return $claimRate->insurance_rate / 100 * $insuranceAmount;
    }

    /**
     * Calculate cash claim amount based on rate.
     */
    public function cashClaim(float $cashAmount, int $doctorId): float
    {
        $claimRate = ClaimRate::where(['doctor_id' => $doctorId, 'status' => 'active'])->first();

        if (!$claimRate) {
            return 0;
        }

        return $claimRate->cash_rate / 100 * $cashAmount;
    }

    /**
     * Get payment balance for a claim.
     */
    public function getPaymentBalance(int $claimId, float $totalClaims): float
    {
        $paidAmount = DoctorClaimPayment::where('doctor_claim_id', $claimId)->sum('amount');

        return $totalClaims - $paidAmount;
    }

    /**
     * Approve a doctor claim (store).
     */
    public function approveClaim(int $id, float $insuranceAmount, float $cashAmount): bool
    {
        return (bool) DoctorClaim::where('id', $id)->update([
            'insurance_amount' => $insuranceAmount,
            'cash_amount' => $cashAmount,
            'status' => 'Approved',
            'approved_by' => Auth::User()->id,
        ]);
    }

    /**
     * Get a single claim for editing.
     */
    public function getClaim(int $id): ?DoctorClaim
    {
        return DoctorClaim::where('id', $id)->first();
    }

    /**
     * Update a doctor claim.
     */
    public function updateClaim(int $id, float $insuranceAmount, float $cashAmount): bool
    {
        return (bool) DoctorClaim::where('id', $id)->update([
            'insurance_amount' => $insuranceAmount,
            'cash_amount' => $cashAmount,
            'status' => 'Approved',
            'approved_by' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a doctor claim (soft-delete).
     */
    public function deleteClaim(int $id): bool
    {
        return (bool) DoctorClaim::where('id', $id)->delete();
    }
}
