<?php

namespace App\Services;

use App\ClaimRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimRateService
{
    /**
     * Get active claim rates list for DataTables.
     */
    public function getClaimRateList(): Collection
    {
        return DB::table('claim_rates')
            ->join('users', 'users.id', 'claim_rates.doctor_id')
            ->whereNull('claim_rates.deleted_at')
            ->where('claim_rates.status', 'active')
            ->select('claim_rates.*', 'users.surname', 'users.othername')
            ->groupBy('claim_rates.doctor_id')
            ->orderBy('claim_rates.updated_at', 'desc')
            ->get();
    }

    /**
     * Get a single claim rate with doctor details for editing.
     */
    public function getClaimRateForEdit(int $id): ?object
    {
        return DB::table('claim_rates')
            ->join('users', 'users.id', 'claim_rates.doctor_id')
            ->where('claim_rates.id', $id)
            ->select('claim_rates.*', 'users.surname', 'users.othername')
            ->first();
    }

    /**
     * Create a new claim rate, deactivating any previous rate for the doctor.
     */
    public function createClaimRate(array $data): ?ClaimRate
    {
        // Check if there is a previous rate for this doctor and deactivate it
        $hasClaim = ClaimRate::where('doctor_id', $data['doctor_id'])->first();
        if ($hasClaim !== null) {
            ClaimRate::where('doctor_id', $data['doctor_id'])->update(['status' => ClaimRate::STATUS_DEACTIVATED]);
        }

        return ClaimRate::create([
            'doctor_id' => $data['doctor_id'],
            'cash_rate' => $data['cash_rate'],
            'insurance_rate' => $data['insurance_rate'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update a claim rate.
     */
    public function updateClaimRate(int $id, array $data): bool
    {
        return (bool) ClaimRate::where('id', $id)->update([
            'doctor_id' => $data['doctor_id'],
            'cash_rate' => $data['cash_rate'],
            'insurance_rate' => $data['insurance_rate'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a claim rate.
     */
    public function deleteClaimRate(int $id): bool
    {
        return (bool) ClaimRate::where('id', $id)->delete();
    }
}
