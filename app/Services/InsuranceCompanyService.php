<?php

namespace App\Services;

use App\InsuranceCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InsuranceCompanyService
{
    private const CACHE_KEY_ALL = 'insurance_companies:all';
    private const CACHE_TTL = 86400; // 24h

    /**
     * Get insurance companies list for DataTables.
     */
    public function getCompanyList(): Collection
    {
        return DB::table('insurance_companies')
            ->leftJoin('users', 'users.id', 'insurance_companies._who_added')
            ->whereNull('insurance_companies.deleted_at')
            ->select(['insurance_companies.*', 'users.surname'])
            ->orderBy('insurance_companies.id', 'desc')
            ->get();
    }

    /**
     * Get all insurance companies (cached, for dropdowns).
     */
    public function getAllCompanies(): Collection
    {
        return Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL, function () {
            return InsuranceCompany::whereNull('deleted_at')->orderBy('name')->get();
        });
    }

    /**
     * Search/filter companies by name (for Select2).
     */
    public function filterCompanies(string $keyword): array
    {
        $data = InsuranceCompany::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name];
        }

        return $formatted;
    }

    /**
     * Get a single company for editing.
     */
    public function getCompanyForEdit(int $id): ?InsuranceCompany
    {
        return InsuranceCompany::where('id', $id)->first();
    }

    /**
     * Create a new insurance company.
     */
    public function createCompany(array $data): ?InsuranceCompany
    {
        $company = InsuranceCompany::create([
            'name' => $data['name'],
            'phone_no' => $data['phone_no'] ?? null,
            'email' => $data['email'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);

        Cache::forget(self::CACHE_KEY_ALL);

        return $company;
    }

    /**
     * Update an insurance company.
     */
    public function updateCompany(int $id, array $data): bool
    {
        $result = (bool) InsuranceCompany::where('id', $id)->update([
            'name' => $data['name'],
            'phone_no' => $data['phone_no'] ?? null,
            'email' => $data['email'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);

        Cache::forget(self::CACHE_KEY_ALL);

        return $result;
    }

    /**
     * Delete an insurance company.
     */
    public function deleteCompany(int $id): bool
    {
        $result = (bool) InsuranceCompany::where('id', $id)->delete();

        Cache::forget(self::CACHE_KEY_ALL);

        return $result;
    }
}
