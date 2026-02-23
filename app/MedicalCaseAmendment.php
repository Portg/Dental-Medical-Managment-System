<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class MedicalCaseAmendment extends Model implements AuditableContract
{
    use Auditable;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'medical_case_id', 'requested_by', 'approved_by',
        'amendment_reason', 'amendment_fields', 'old_values', 'new_values',
        'status', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'amendment_fields' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $auditExclude = ['updated_at', 'created_at'];

    public function generateTags(): array
    {
        return ['medical-record', 'amendment'];
    }

    public function medicalCase()
    {
        return $this->belongsTo('App\MedicalCase', 'medical_case_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo('App\User', 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo('App\User', 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForCase($query, $caseId)
    {
        return $query->where('medical_case_id', $caseId);
    }

    /**
     * Approve amendment and apply changes to the medical case.
     */
    public function approve(int $approverId, ?string $reviewNotes = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approverId;
        $this->reviewed_at = now();
        $this->review_notes = $reviewNotes;
        $this->save();

        // Apply the amendment to the medical case
        $case = $this->medicalCase;
        if ($case && $this->new_values) {
            $case->fill($this->new_values);
            $case->increment('version_number');
            $case->recordModification($this->amendment_reason);
            $case->save();
        }

        return true;
    }

    /**
     * Reject the amendment.
     */
    public function reject(int $approverId, ?string $reviewNotes = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->approved_by = $approverId;
        $this->reviewed_at = now();
        $this->review_notes = $reviewNotes;
        $this->save();

        return true;
    }
}
