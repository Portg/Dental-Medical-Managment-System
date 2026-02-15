<?php

namespace App\Services;

use App\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveTypeService
{
    /**
     * Get leave types list for DataTables.
     */
    public function getLeaveTypeList(): Collection
    {
        return DB::table('leave_types')
            ->leftJoin('users', 'users.id', 'leave_types._who_added')
            ->whereNull('leave_types.deleted_at')
            ->select(['leave_types.*', 'users.surname'])
            ->orderBy('leave_types.id', 'desc')
            ->get();
    }

    /**
     * Search/filter leave types by name (for Select2).
     */
    public function filterLeaveTypes(string $keyword): array
    {
        $data = LeaveType::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name];
        }

        return $formatted;
    }

    /**
     * Get all active leave types (for dropdowns).
     */
    public function getAllLeaveTypes(): array
    {
        $data = LeaveType::whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        $formatted = [];
        foreach ($data as $item) {
            $formatted[] = [
                'id' => $item->id,
                'text' => $item->name,
                'max_days' => $item->max_days,
            ];
        }

        return $formatted;
    }

    /**
     * Get a single leave type for editing.
     */
    public function getLeaveTypeForEdit(int $id): ?LeaveType
    {
        return LeaveType::where('id', $id)->first();
    }

    /**
     * Create a new leave type.
     */
    public function createLeaveType(array $data): ?LeaveType
    {
        return LeaveType::create([
            'name' => $data['name'],
            'max_days' => $data['max_days'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update a leave type.
     */
    public function updateLeaveType(int $id, array $data): bool
    {
        return (bool) LeaveType::where('id', $id)->update([
            'name' => $data['name'],
            'max_days' => $data['max_days'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a leave type.
     */
    public function deleteLeaveType(int $id): bool
    {
        return (bool) LeaveType::where('id', $id)->delete();
    }
}
