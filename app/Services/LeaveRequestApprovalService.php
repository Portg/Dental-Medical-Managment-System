<?php

namespace App\Services;

use App\leaveRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveRequestApprovalService
{
    /**
     * Get all leave requests for approval listing.
     */
    public function getAllLeaveRequests(): Collection
    {
        return DB::table('leave_requests')
            ->leftJoin('leave_types', 'leave_types.id', 'leave_requests.leave_type_id')
            ->leftJoin('users', 'users.id', 'leave_requests._who_added')
            ->whereNull('leave_requests.deleted_at')
            ->select(['leave_requests.*', 'leave_types.name', 'users.surname', 'users.othername'])
            ->orderBy('leave_requests.id', 'desc')
            ->get();
    }

    /**
     * Approve a leave request.
     */
    public function approveRequest(int $id, int $approvedBy): bool
    {
        return (bool) leaveRequest::where('id', $id)->update([
            'action_date' => date('yyy-mm-dd'),
            'status' => leaveRequest::STATUS_APPROVED,
            '_approved_by' => $approvedBy,
        ]);
    }

    /**
     * Reject a leave request.
     */
    public function rejectRequest(int $id, int $approvedBy): bool
    {
        return (bool) leaveRequest::where('id', $id)->update([
            'action_date' => date('yyy-mm-dd'),
            'status' => leaveRequest::STATUS_REJECTED,
            '_approved_by' => $approvedBy,
        ]);
    }
}
