<?php

namespace App\Services;

use App\PatientTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientTagService
{
    /**
     * Get filtered tag list for DataTables.
     */
    public function getTagList(array $filters): Collection
    {
        $query = DB::table('patient_tags')
            ->leftJoin('users', 'users.id', 'patient_tags._who_added')
            ->whereNull('patient_tags.deleted_at')
            ->select(
                'patient_tags.*',
                'users.surname as added_by_name',
                DB::raw("(SELECT COUNT(*) FROM patient_tag_pivot WHERE tag_id = patient_tags.id) as patients_count")
            );

        // Quick search filter
        if (!empty($filters['quick_search'])) {
            $search = $filters['quick_search'];
            $query->where('patient_tags.name', 'like', '%' . $search . '%');
        }

        // Status filter (use is_numeric to handle '0' correctly)
        if (isset($filters['status']) && is_numeric($filters['status'])) {
            $query->where('patient_tags.is_active', $filters['status']);
        }

        return $query->orderBy('patient_tags.sort_order', 'asc')
            ->orderBy('patient_tags.name', 'asc')
            ->get();
    }

    /**
     * Get active tags for Select2 dropdown.
     */
    public function getActiveTagsForSelect(?string $search = null): Collection
    {
        $query = PatientTag::active()->ordered();

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->select('id', 'name', 'color', 'icon')->get()->map(function ($tag) {
            return [
                'id' => $tag->id,
                'text' => $tag->name,
                'color' => $tag->color,
                'icon' => $tag->icon,
            ];
        });
    }

    /**
     * Create a new patient tag.
     */
    public function createTag(array $data): ?PatientTag
    {
        $data['_who_added'] = Auth::user()->id;

        return PatientTag::create($data);
    }

    /**
     * Get a single tag by ID.
     */
    public function getTag(int $id): PatientTag
    {
        return PatientTag::findOrFail($id);
    }

    /**
     * Update a patient tag.
     */
    public function updateTag(int $id, array $data): bool
    {
        return (bool) PatientTag::where('id', $id)->update($data);
    }

    /**
     * Delete a patient tag and its pivot entries.
     */
    public function deleteTag(int $id): bool
    {
        DB::table('patient_tag_pivot')->where('tag_id', $id)->delete();

        return (bool) PatientTag::where('id', $id)->delete();
    }
}
