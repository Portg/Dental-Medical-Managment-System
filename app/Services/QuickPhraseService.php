<?php

namespace App\Services;

use App\QuickPhrase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickPhraseService
{
    /**
     * Get filtered phrase list for DataTables.
     */
    public function getPhraseList(array $filters): Collection
    {
        $query = DB::table('quick_phrases')
            ->leftJoin('users', 'users.id', 'quick_phrases.user_id')
            ->whereNull('quick_phrases.deleted_at')
            ->select(
                'quick_phrases.*',
                'users.surname as user_name'
            );

        // Filter by category
        if (!empty($filters['category'])) {
            $query->where('quick_phrases.category', $filters['category']);
        }

        // Filter by scope
        if (!empty($filters['scope'])) {
            $query->where('quick_phrases.scope', $filters['scope']);
        }

        return $query->orderBy('quick_phrases.category', 'asc')
            ->orderBy('quick_phrases.shortcut', 'asc')
            ->get();
    }

    /**
     * Create a new quick phrase.
     */
    public function createPhrase(array $data): ?QuickPhrase
    {
        $data['_who_added'] = Auth::user()->id;

        if (($data['scope'] ?? null) === 'personal') {
            $data['user_id'] = Auth::user()->id;
        } else {
            $data['user_id'] = null;
        }

        return QuickPhrase::create($data);
    }

    /**
     * Get a single phrase by ID.
     */
    public function getPhrase(int $id): QuickPhrase
    {
        return QuickPhrase::with('user')->findOrFail($id);
    }

    /**
     * Update a quick phrase.
     */
    public function updatePhrase(int $id, array $data): bool
    {
        if (($data['scope'] ?? null) === 'personal') {
            $data['user_id'] = Auth::user()->id;
        } else {
            $data['user_id'] = null;
        }

        return (bool) QuickPhrase::where('id', $id)->update($data);
    }

    /**
     * Delete a quick phrase (soft-delete).
     */
    public function deletePhrase(int $id): bool
    {
        return (bool) QuickPhrase::where('id', $id)->delete();
    }

    /**
     * Search phrases for quick insertion.
     */
    public function searchPhrases(?string $query, ?string $category): Collection
    {
        $userId = Auth::user()->id;

        $builder = QuickPhrase::active()
            ->forUser($userId)
            ->select('id', 'shortcut', 'phrase', 'category');

        if ($query) {
            $builder->search($query);
        }

        if ($category) {
            $builder->byCategory($category);
        }

        return $builder->orderBy('category', 'asc')
            ->orderBy('shortcut', 'asc')
            ->limit(50)
            ->get();
    }
}
