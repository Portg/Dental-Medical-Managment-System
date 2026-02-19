<?php

namespace App\Services;

use App\BirthDayMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BirthDayMessageService
{
    /**
     * Get birthday messages with optional search filter.
     */
    public function getList(array $filters): Collection
    {
        $query = DB::table('birth_day_messages')
            ->leftJoin('users', 'users.id', 'birth_day_messages._who_added')
            ->whereNull('birth_day_messages.deleted_at')
            ->select(['birth_day_messages.*', 'users.surname']);

        $search = $filters['search'] ?? '';
        if (is_array($search)) {
            $search = $search['value'] ?? '';
        }
        if ($search !== '') {
            $query->where('birth_day_messages.message', 'like', '%' . $search . '%');
        }

        return $query->orderBy('birth_day_messages.id', 'desc')->get();
    }

    /**
     * Find a single birthday message by ID.
     */
    public function find(int $id): ?BirthDayMessage
    {
        return BirthDayMessage::where('id', $id)->first();
    }

    /**
     * Create a new birthday message.
     */
    public function create(string $message): ?BirthDayMessage
    {
        return BirthDayMessage::create([
            'message' => $message,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing birthday message.
     */
    public function update(int $id, string $message): bool
    {
        return (bool) BirthDayMessage::where('id', $id)->update([
            'message' => $message,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a birthday message (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) BirthDayMessage::where('id', $id)->delete();
    }
}
