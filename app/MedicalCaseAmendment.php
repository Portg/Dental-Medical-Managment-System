<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use App\Concerns\SerializesDatesInAppTimezone;

class MedicalCaseAmendment extends Model implements AuditableContract
{
    use SerializesDatesInAppTimezone;
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
        'reviewed_at' => 'datetime:Y-m-d H:i',
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

            // 病例一提交就锁了，之后改「下次复诊日期」只能走修改申请 —— 而这条路径
            // 是直接 fill()->save() 的，不经过 MedicalCaseService。不在这里补一刀，
            // 审批通过后复诊待办还停在旧日期上，且没有任何地方会提示不一致。
            // 两个键都要认：改日期要移动待办，取消勾选（改为按需复诊）要撤销待办。
            // 只认 next_visit_date 的话，医生把「生成复诊待办」取消掉、审批也通过了，
            // 前台那条待办还挂在列表上等人打电话。
            $followupKeys = ['next_visit_date', 'auto_create_followup'];
            $touchesFollowup = count(array_intersect($followupKeys, array_keys($this->new_values))) > 0;
            if ($touchesFollowup && !$case->is_draft) {
                app(\App\Services\MedicalCaseService::class)->syncFollowupFromCase($case->refresh());
            }
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
