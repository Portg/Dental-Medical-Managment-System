<?php

namespace App\Services;

use App\DoctorClaimPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorClaimPaymentService
{
    /**
     * Get payments for a specific claim.
     */
    public function getPaymentsByClaim(int $claimId): Collection
    {
        return DoctorClaimPayment::where('doctor_claim_id', $claimId)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get doctor info for a claim.
     */
    public function getDoctorForClaim(int $claimId): ?object
    {
        return DB::table('doctor_claims')
            ->join('users', 'users.id', 'doctor_claims._who_added')
            ->where('doctor_claims.id', $claimId)
            ->whereNull('doctor_claims.deleted_at')
            ->first();
    }

    /**
     * Find a payment by ID.
     */
    public function findPayment(int $id): ?DoctorClaimPayment
    {
        return DoctorClaimPayment::where('id', $id)->first();
    }

    /**
     * Create a new claim payment.
     */
    public function createPayment(string $paymentDate, string $amount, int $claimId, int $userId): ?DoctorClaimPayment
    {
        return DoctorClaimPayment::create([
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'doctor_claim_id' => $claimId,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Update an existing claim payment.
     */
    public function updatePayment(int $id, string $paymentDate, string $amount, int $userId): bool
    {
        return (bool) DoctorClaimPayment::where('id', $id)->update([
            'payment_date' => $paymentDate,
            'amount' => $amount,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Delete a claim payment (soft-delete).
     */
    public function deletePayment(int $id): bool
    {
        return (bool) DoctorClaimPayment::where('id', $id)->delete();
    }
}
