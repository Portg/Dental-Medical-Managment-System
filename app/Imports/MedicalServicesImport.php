<?php

namespace App\Imports;

use App\ServiceCategory;
use App\MedicalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MedicalServicesImport implements ToCollection, WithHeadingRow, WithValidation
{
    public int $importedCount = 0;

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $name = trim($row['name'] ?? '');
                if (!$name) {
                    continue;
                }

                $categoryId = null;
                if (!empty($row['category'])) {
                    $cat = ServiceCategory::firstOrCreate(
                        ['name' => trim($row['category'])],
                        ['sort_order' => 0, 'is_active' => true, '_who_added' => Auth::id()]
                    );
                    $categoryId = $cat->id;
                }

                MedicalService::updateOrCreate(
                    ['name' => $name],
                    [
                        'price'       => $row['price'] ?? 0,
                        'unit'        => $row['unit'] ?? null,
                        'category_id' => $categoryId,
                        '_who_added'  => Auth::id(),
                    ]
                );
                $this->importedCount++;
            }
        });

        Cache::forget('billing_service_category_tree');
    }

    public function rules(): array
    {
        return ['name' => 'required'];
    }
}
