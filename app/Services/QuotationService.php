<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\Quotation;
use App\QuotationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    /**
     * Get filtered quotation list for DataTables.
     */
    public function getQuotationList(array $filters): Collection
    {
        $query = DB::table('quotations')
            ->join('patients', 'patients.id', 'quotations.patient_id')
            ->join('users', 'users.id', 'quotations._who_added')
            ->whereNull('quotations.deleted_at')
            ->select('quotations.*', 'patients.surname', 'patients.othername', 'users.othername as addedBy');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'patients');
            });
        } elseif (!empty($filters['quotation_no'])) {
            $query->where('quotations.quotation_no', '=', $filters['quotation_no']);
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE_FORMAT(quotations.updated_at, \'%Y-%m-%d\')'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        }

        return $query->orderBy('quotations.id', 'desc')->get();
    }

    /**
     * Create a quotation with its items.
     */
    public function createQuotation(int $patientId, array $items, int $userId): ?Quotation
    {
        $quotation = Quotation::create([
            'quotation_no' => Quotation::QuotationNo(),
            'patient_id' => $patientId,
            '_who_added' => $userId,
        ]);

        if (!$quotation) {
            return null;
        }

        foreach ($items as $value) {
            QuotationItem::create([
                'qty' => $value['qty'],
                'amount' => $value['amount'],
                'quotation_id' => $quotation->id,
                'medical_service_id' => $value['medical_service_id'],
                '_who_added' => $userId,
            ]);
        }

        return $quotation;
    }

    /**
     * Get quotation show data (patient + quotation).
     */
    public function getQuotationShowData(int $quotationId): array
    {
        $patient = DB::table('quotations')
            ->join('patients', 'patients.id', 'quotations.patient_id')
            ->where('quotations.id', $quotationId)
            ->select('patients.*')
            ->first();

        $quotation = Quotation::where('id', $quotationId)->first();

        return [
            'patient' => $patient,
            'quotation_id' => $quotationId,
            'quotation' => $quotation,
        ];
    }

    /**
     * Get quotation print/email data (patient, quotation, items).
     */
    public function getQuotationPrintData(int $quotationId): array
    {
        $patient = DB::table('quotations')
            ->leftJoin('patients', 'patients.id', 'quotations.patient_id')
            ->where('quotations.id', $quotationId)
            ->select('patients.*')
            ->first();

        $quotation = Quotation::where('id', $quotationId)->first();

        $quotationItems = DB::table('quotation_items')
            ->join('medical_services', 'medical_services.id', 'quotation_items.medical_service_id')
            ->join('users', 'users.id', 'quotation_items._who_added')
            ->whereNull('quotation_items.deleted_at')
            ->where('quotation_items.quotation_id', $quotationId)
            ->select('quotation_items.*', 'medical_services.name', 'users.othername')
            ->orderBy('quotation_items.updated_at', 'desc')
            ->get();

        return [
            'patient' => $patient,
            'quotation' => $quotation,
            'quotation_items' => $quotationItems,
        ];
    }

    /**
     * Get quotation share details.
     */
    public function getShareDetails(int $quotationId): ?object
    {
        return DB::table('quotations')
            ->join('patients', 'patients.id', 'quotations.patient_id')
            ->join('users', 'users.id', 'quotations._who_added')
            ->whereNull('quotations.deleted_at')
            ->where('quotations.id', '=', $quotationId)
            ->select('quotations.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy')
            ->orderBy('quotations.id', 'desc')
            ->first();
    }
}
