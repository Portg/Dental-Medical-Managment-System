<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MedicalServicesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DB::table('medical_services')
            ->leftJoin('service_categories', 'service_categories.id', '=', 'medical_services.category_id')
            ->whereNull('medical_services.deleted_at')
            ->select([
                'medical_services.name',
                'medical_services.price',
                'medical_services.unit',
                'service_categories.name as category',
            ])
            ->orderBy('medical_services.id')
            ->get();
    }

    public function headings(): array
    {
        return ['name', 'price', 'unit', 'category'];
    }
}
