<?php

namespace App\Services;

use App\leaveRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    /**
     * Get leave requests for the current user.
     */
    public function getLeaveRequestsForUser(int $userId): Collection
    {
        return DB::table('leave_requests')
            ->leftJoin('leave_types', 'leave_types.id', 'leave_requests.leave_type_id')
            ->whereNull('leave_requests.deleted_at')
            ->where('leave_requests._who_added', '=', $userId)
            ->select(['leave_requests.*', 'leave_types.name'])
            ->orderBy('leave_requests.id', 'desc')
            ->get();
    }

    /**
     * Get a single leave request for editing.
     */
    public function getLeaveRequestForEdit(int $id): ?object
    {
        return DB::table('leave_requests')
            ->leftJoin('leave_types', 'leave_types.id', 'leave_requests.leave_type_id')
            ->where('leave_requests.id', '=', $id)
            ->select(['leave_requests.*', 'leave_types.name'])
            ->orderBy('leave_requests.id', 'desc')
            ->first();
    }

    /**
     * Create a new leave request.
     */
    public function createLeaveRequest(array $data, int $userId): ?leaveRequest
    {
        return leaveRequest::create([
            'leave_type_id' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'duration' => $data['duration'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Update an existing leave request.
     */
    public function updateLeaveRequest(int $id, array $data, int $userId): bool
    {
        return (bool) leaveRequest::where('id', $id)->update([
            'leave_type_id' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'duration' => $data['duration'],
            '_who_added' => $userId,
        ]);
    }

    /**
     * Delete a leave request.
     */
    public function deleteLeaveRequest(int $id): bool
    {
        return (bool) leaveRequest::where('id', $id)->delete();
    }
}
