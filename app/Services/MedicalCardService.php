<?php

namespace App\Services;

use App\MedicalCard;
use App\MedicalCardItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicalCardService
{
    /**
     * Get all medical cards for the index listing.
     */
    public function getMedicalCardList(): Collection
    {
        return DB::table('medical_cards')
            ->join('patients', 'patients.id', 'medical_cards.patient_id')
            ->join('users', 'users.id', 'medical_cards._who_added')
            ->whereNull('medical_cards.deleted_at')
            ->select('medical_cards.*', 'patients.surname', 'patients.othername', 'users.othername as added_by')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get medical cards for a specific patient.
     */
    public function getIndividualMedicalCards(int $patientId): Collection
    {
        return DB::table('medical_cards')
            ->join('patients', 'patients.id', 'medical_cards.patient_id')
            ->whereNull('medical_cards.deleted_at')
            ->where('medical_cards.patient_id', $patientId)
            ->select('medical_cards.*', 'patients.surname', 'patients.othername')
            ->get();
    }

    /**
     * Create a medical card with uploaded file items.
     *
     * @param array $data       ['card_type' => string, 'patient_id' => int]
     * @param array $files      Array of uploaded files
     * @return MedicalCard|null
     */
    public function createMedicalCard(array $data, array $files): ?MedicalCard
    {
        $card = MedicalCard::create([
            'card_type' => $data['card_type'],
            'patient_id' => $data['patient_id'],
            '_who_added' => Auth::User()->id,
        ]);

        if ($card) {
            foreach ($files as $key => $value) {
                $imageName = time() . $key . '.' . $value->getClientOriginalExtension();
                $value->move('uploads/medical_cards', $imageName);
                MedicalCardItem::create([
                    'medical_card_id' => $card->id,
                    'card_photo' => $imageName,
                    '_who_added' => Auth::User()->id,
                ]);
            }
        }

        return $card;
    }

    /**
     * Get medical card detail for the show page.
     */
    public function getMedicalCardDetail(int $id): array
    {
        $images = MedicalCardItem::where('medical_card_id', $id)->get();
        $patient = DB::table('medical_cards')
            ->join('patients', 'patients.id', 'medical_cards.patient_id')
            ->whereNull('medical_cards.deleted_at')
            ->where('medical_cards.id', $id)
            ->select('patients.*')
            ->first();

        return compact('images', 'patient');
    }

    /**
     * Get medical card data for editing.
     */
    public function getMedicalCardForEdit(int $id): object
    {
        return DB::table('medical_cards')
            ->join('patients', 'patients.id', 'medical_cards.patient_id')
            ->where('medical_cards.id', $id)
            ->select('medical_cards.*', 'patients.surname', 'patients.othername')
            ->first();
    }

    /**
     * Delete a medical card (soft-delete).
     */
    public function deleteMedicalCard(int $id): bool
    {
        return (bool) MedicalCard::where('id', $id)->delete();
    }
}
